<?php
namespace Digiselfie\Login\Controller\Oauth2Callback;

/**
 * Digiselfie Login Digiselfie Controller
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Index extends \Magento\Framework\App\Action\Action
{

    protected $referer = null;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $generic;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Digiselfie\Login\Helper\Digiselfie
     */
    protected $loginDigiselfieHelper;

    /**
     * @var \Digiselfie\Login\Helper\Data
     */
    protected $digiselfieData;

    /**
     * @var \Digiselfie\Login\Model\Digiselfie\Client
     */
    protected $loginDigiselfieClient;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrlInterface;

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userUserFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Session\Generic $generic,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Digiselfie\Login\Helper\Digiselfie $loginDigiselfieHelper,
        \Digiselfie\Login\Helper\Data $digiselfieData,
        \Digiselfie\Login\Model\Digiselfie\Client $loginDigiselfieClient,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Backend\Model\UrlInterface $backendUrlInterface,
        \Magento\User\Model\UserFactory $userUserFactory
    ) {
        $this->generic = $generic;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->loginDigiselfieHelper = $loginDigiselfieHelper;
        $this->digiselfieData = $digiselfieData;
        $this->loginDigiselfieClient = $loginDigiselfieClient;
        $this->backendAuthSession = $backendAuthSession;
        $this->backendSession = $backendSession;
        $this->backendUrlInterface = $backendUrlInterface;
        $this->userUserFactory = $userUserFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct(
            $context
        );
    }


    public function execute()
    {
        try {
            $this->_connectCallback();
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        if(!empty($this->referer)) {
            $resultRedirect->setUrl( $this->referer );
        } else {
            $resultRedirect->setPath( 'customer/account' );
        }
        return $resultRedirect;
    }



    protected function _connectCallback() {
        $errorCode = $this->getRequest()->getParam('error');
        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
        $userModel = 'customer';

        if(!($errorCode || $code) && !$state) {
            // Direct route access - deny
            return;
        }
        $this->referer = $this->generic->getDigiselfieRedirect();

        $this->generic->getDigiselfieCsrf('');
        if($errorCode) {
            // Digiselfie API read light - abort
            if($errorCode === 'access_denied') {
                $this->messageManager
                    ->addNotice(
                        __('Digiselfie Connect process aborted.')
                    );

                return;
            }
            throw new \Exception(
                sprintf(
                    __('Sorry, "%s" error occured. Please try again.'),
                    $errorCode
                )
            );
            return;
        }


        if ($code) {
            // Digiselfie API green light - proceed
            $client = $this->loginDigiselfieClient;

            $userInfo = $client->api();

            $customersByDigiselfieId = $this->loginDigiselfieHelper->getCustomersByDigiselfieId($userInfo->ID);

            if ($customersByDigiselfieId->getFirstItem() instanceof \Magento\User\Model\User) {
                $userModel = 'admin';

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $user = $this->backendAuthSession->getUser();
                if ($this->backendAuthSession->isLoggedIn()){
                    $this->messageManager->addNotice(
                        __('Your Digiselfie account is already connected to admin panel.')
                    );
                    if(class_exists("Magento\Security\Model\AdminSessionsManager")) {
                        $adminSessionManager = $objectManager->get('Magento\Security\Model\AdminSessionsManager');
                        $adminSessionManager->processLogin();
                    }
                    $backendUrl = $objectManager->get('Magento\Backend\Model\UrlInterface');
                    $path = $backendUrl->getStartupPageUrl();
                    $url = $backendUrl->getUrl($path);
                    $this->referer = $this->backendUrlInterface->getUrl($this->userUserFactory->create()->getStartupPageUrl(), array('_current' => false));
                    $this->referer .= '?SID=' . $this->backendAuthSession->getSessionId();

                    return;
                }

                if ($customersByDigiselfieId->getFirstItem()->getUserId()) {
                    $user = $customersByDigiselfieId->getFirstItem();

                    if(!$this->loginDigiselfieHelper->connectByAdmin($user)) {
                        $this->messageManager->addError(
                            __('Sorry, but we could not connect to admin panel using your Digiselfie account.')
                        );
                        return;
                    }

                    $cookieManager = $objectManager->get('Magento\Framework\Stdlib\CookieManagerInterface');
                    $cookieValue = $this->backendAuthSession->getSessionId();
                    if ($cookieValue) {
                        $sessionConfig = $objectManager->get('Magento\Backend\Model\Session\AdminConfig');
                        $cookiePath = $sessionConfig->getCookiePath();
                        $cookieMetadata = $objectManager->get('Magento\Framework\Stdlib\Cookie\CookieMetadataFactory')
                            ->createPublicCookieMetadata()
                            ->setDuration(3600)
                            ->setPath($cookiePath)
                            ->setDomain($sessionConfig->getCookieDomain())
                            ->setSecure($sessionConfig->getCookieSecure())
                            ->setHttpOnly($sessionConfig->getCookieHttpOnly());
                        $cookieManager->setPublicCookie('admin', $cookieValue, $cookieMetadata);
                        if(class_exists("Magento\Security\Model\AdminSessionsManager")) {
                            $adminSessionManager = $objectManager->get('Magento\Security\Model\AdminSessionsManager');
                            $adminSessionManager->processLogin();
                        }
                    }

                    /** @var \Magento\Backend\Model\UrlInterface $backendUrl */
                    $backendUrl = $objectManager->get('Magento\Backend\Model\UrlInterface');
                    $path = $backendUrl->getStartupPageUrl();
                    $url = $backendUrl->getUrl($path);


                    //$url = $this->backendUrlInterface->getUrl($this->userUserFactory->create()->getStartupPageUrl(), array('_current' => false));
                    $this->referer = $url . '?SID=' . $cookieValue ;

                    $this->messageManager->addSuccess(
                        __('You have successfully logged in to admin panel using your Digiselfie account.')
                    );

                    return;
                }
            }

            if($this->customerSession->isLoggedIn()) {
                // Logged in user
                if($customersByDigiselfieId->count()) {
                    // Digiselfie account already connected to other account - deny
                     $this->messageManager->addNotice(
                            __('Your Digiselfie account is already connected to one of our store accounts.')
                        );
                    return;
                }
                // Connect from account dashboard - attach
                $customer = $this->customerSession->getCustomer();
                $this->loginDigiselfieHelper->connectByDigiselfieId(
                    $customer,
                    $userInfo->ID
                );
                $this->messageManager->addSuccess(
                    __('Your Digiselfie account is now connected to your new user accout at our store. You can login next time by the Digiselfie app or Store user account. Account confirmation mail has been sent to your email.')
                );
                return;
            }

            if($customersByDigiselfieId->count()) {
                // Existing connected user - login
                $customer = $customersByDigiselfieId->getFirstItem();
                $this->loginDigiselfieHelper->loginByCustomer($customer);
                $this->messageManager
                    ->addSuccess(
                        __('You have successfully logged in using your Digiselfie account.')
                    );
                return;
            }

            $email = $userInfo->ID . '@' . 'digiselfie.com';
            if(!empty($userInfo->Emails[0]->Address)) {
                $email = $userInfo->Emails[0]->Address;
            }

            $customersByEmail = $this->loginDigiselfieHelper->getCustomersByEmail($email);

            if($customersByEmail->count())  {
                // Email account already exists - attach, login
                $customer = $customersByEmail->getFirstItem();
                $digiselfie_login_id = $customer->getDigiselfieLoginId();

                if(empty($digiselfie_login_id)) {

                    // force to delete DigiSelfie cookie variables before set
                    $this->digiselfieData->deleteCookie('digiselfie_login_id');
                    $this->digiselfieData->deleteCookie('ds_customer_id');

                    // Cookie lifetime 5 min
                    $this->digiselfieData->setCookie('digiselfie_login_id', $userInfo->ID, 60*5);
                    $this->digiselfieData->setCookie('ds_customer_id', $customer->getId(), 60*5);

                    $this->messageManager->addNotice(
                        __('We found you already have an account in our system. Please enter your username and password to link your account with your digiselfie.')
                    );
                    return;
                }
                elseif($digiselfie_login_id != $userInfo->ID) {
                    $this->messageManager->addError(
                        __('We found your account, but you need to login with your other digiselfie.')
                    );
                    return;
                }

                $this->loginDigiselfieHelper->connectByDigiselfieId(
                    $customer,
                    $userInfo->ID
                );

                $this->messageManager->addSuccess(
                    __('We find you already have an account at our store. Your Digiselfie account is now connected to your store account. Account confirmation mail has been sent to your email.')
                );
                return;
            }

            // New connection - create, attach, login
            if(empty($userInfo->FirstName)) {
                throw new \Exception(
                    __('Sorry, could not retrieve your Digiselfie first name. Please try again.')
                );
            }

            /* NOT REQUIRED in DigiSelfie
             * if(empty($userInfo->LastName)) {
                throw new Exception(
                    __('Sorry, could not retrieve your Digiselfie last name. Please try again.')
                );
            }*/


            $this->loginDigiselfieHelper->connectByCreatingAccount(
                $email,
                $userInfo->FirstName,
                $userInfo->LastName,
                $userInfo->ID
            );

            $this->messageManager->addSuccess(
                __('Your Digiselfie account is now connected to your new user accout at our store. You can login next time by the Digiselfie app or Store user account. Account confirmation mail has been sent to your email.')
            );
        }
    }


}
