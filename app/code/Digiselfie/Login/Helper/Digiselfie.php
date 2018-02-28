<?php
/**
 * Digiselfie Login Digiselfie Helper
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

namespace Digiselfie\Login\Helper;

class Digiselfie extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerCustomerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userUserFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $generic;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrlInterface;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\ImageFactory
     */
    protected $imageFactory;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;


    protected $objectManager;
    protected $configLoader;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\User\Model\UserFactory $userUserFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Session\Generic $generic,
        \Magento\Backend\Model\UrlInterface $backendUrlInterface,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\ImageFactory $imageFactory,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->imageFactory = $imageFactory;
        $this->customerSession = $customerSession;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->storeManager = $storeManager;
        $this->userUserFactory = $userUserFactory;
        $this->backendAuthSession = $backendAuthSession;
        $this->generic = $generic;
        $this->backendUrlInterface = $backendUrlInterface;
        $this->eventManager = $eventManager;
        $this->mathRandom = $mathRandom;
        $this->productMetadata = $productMetadata;

        $this->objectManager = $objectManager;
        $this->areaList = $this->objectManager->get('Magento\Framework\App\AreaList');
        $this->request = $this->objectManager->get('Magento\Framework\App\Request\Http');
        $this->response = $this->objectManager->get('Magento\Framework\App\Response\Http');
        $this->configLoader = $this->objectManager->get('Magento\Framework\ObjectManager\ConfigLoaderInterface');
        $this->state = $this->objectManager->get('Magento\Framework\App\State');
        $this->filesystem = $this->objectManager->get('Magento\Framework\Filesystem');
        $this->registry = $this->objectManager->get('\Magento\Framework\Registry');


        parent::__construct(
            $context
        );
    }


    public function connectByDigiselfieId(
            \Magento\Customer\Model\Customer $customer,
            $digiselfieId
        )
    {
        if($this->getMagentoVersion() >= "2.1") {
            $customer->setDigiselfieLoginId($digiselfieId);
        }
        else {
            $customerData = $customer->getDataModel();
            $customerData->setCustomAttribute('digiselfie_login_id', $digiselfieId);
            $customer->updateData($customerData);
        }
        $this->customerSession->setCustomerAsLoggedIn($customer);
    }

    public function connectByCreatingAccount(
            $email,
            $firstName,
            $lastName,
            $digiselfieId
        )
    {

        $customer = $this->customerCustomerFactory->create();

        $customer->setWebsiteId($this->storeManager->getWebsite()->getId())
                ->setEmail($email)
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setPassword($this->generatePassword())
                ->save();

        $customer->setConfirmation(null);

        if($this->getMagentoVersion() >= "2.1") {
            $customer->setDigiselfieLoginId($digiselfieId);
        }
        else {
            $customerData = $customer->getDataModel();
            $customerData->setCustomAttribute('digiselfie_login_id', $digiselfieId);
            $customer->updateData($customerData);
        }
        $customer->save();

        //  $customer->sendNewAccountEmail('confirmed', '', $this->storeManager->getStore()->getId());
        $this->customerSession->setCustomerAsLoggedIn($customer);
    }

    public function loginByCustomer(\Magento\Customer\Model\Customer $customer)
    {
        if($customer->getConfirmation()) {
            $customer->setConfirmation(null);
            $customer->save();
        }
        $this->customerSession->setCustomerAsLoggedIn($customer);
    }

    public function getCustomersByDigiselfieId($digiselfieId)
    {
        $customer = $this->customerCustomerFactory->create();

        $collection = $customer->getCollection()
            ->addAttributeToFilter('digiselfie_login_id', array("eq" => $digiselfieId))
            ->setPageSize(1)
            ->load();

        if($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter(
                'website_id',
                $this->storeManager->getWebsite()->getId()
            );
        }

        if($this->customerSession->isLoggedIn()) {
            if($this->customerSession->isLoggedIn()) {
                $collection->addFieldToFilter(
                    'entity_id',
                    array('neq' => $this->customerSession->getCustomerId())
                );
            }
        }



       if(!$collection->count()) {

            // If no one customer found - we need to check if user is admin
            $adminUsers = $this->userUserFactory->create();

                        echo "<pre>";

            $collection = $adminUsers->getCollection()
                ->addFieldToFilter('digiselfie_login_id', array("eq" => $digiselfieId))
                ->setPageSize(1)
                ->load();


            if($this->backendAuthSession->isLoggedIn()) {
                $collection->addFieldToFilter(
                    'user_id',
                    array('neq' => $this->backendAuthSession->getUser()->getId())
                );
            }

       }


        return $collection;
    }

    public function getCustomersByEmail($email)
    {
        $customer = $this->customerCustomerFactory->create();

        $collection = $customer->getCollection()
                ->addFieldToFilter('email', $email)
                ->addAttributeToSelect('digiselfie_login_id')
                ->setPageSize(1);

        if($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter(
                'website_id',
                $this->storeManager->getWebsite()->getId()
            );
        }

        if($this->customerSession->isLoggedIn()) {
            $collection->addFieldToFilter(
                'entity_id',
                array('neq' => $this->customerSession->getCustomerId())
            );
        }

        return $collection;
    }

    public function getProperDimensionsPictureUrl($digiselfieId, $pictureUrl)
    {
        $url = Mage::getBaseUrl(\Magento\Store\Model\Store::URL_TYPE_MEDIA)
                .'digiselfie'
                .'/'
                .'DigiselfieLogin'
                .'/'
                .'digiselfie'
                .'/'
                .$digiselfieId;

        $filename = Mage::getBaseDir(\Magento\Store\Model\Store::URL_TYPE_MEDIA)
                .DS
                .'digiselfie'
                .DS
                .'digiselfielogin'
                .DS
                .'digiselfie'
                .DS
                .$digiselfieId;

        $directory = dirname($filename);

        if (!file_exists($directory) || !is_dir($directory)) {
            if (!@mkdir($directory, 0777, true))
                return null;
        }

        if(!file_exists($filename) ||
                (file_exists($filename) && (time() - filemtime($filename) >= 3600))){
            $client = new \Zend_Http_Client($pictureUrl);
            $client->setStream();
            $response = $client->request('GET');
            stream_copy_to_stream($response->getStream(), fopen($filename, 'w'));

            $imageObj = $this->imageFactory->create($filename);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(true);
            $imageObj->keepFrame(false);
            $imageObj->resize(150, 150);
            $imageObj->save($filename);
        }
        return $url;
    }

    public function connectByAdmin(\Magento\User\Model\User $user)
    {
        $areaCode = 'adminhtml';
        $username = $user->getUserName();
        $this->request->setPathInfo('/admin');

        $this->objectManager->configure($this->configLoader->load('adminhtml'));

        /** @var \Magento\User\Model\User $user */
        $user = $this->objectManager->get('Magento\User\Model\User')->loadByUsername( $username );

        /** @var \Magento\Backend\Model\Auth\Session $session */
        $session = $this->objectManager->get('Magento\Backend\Model\Auth\Session');
        $session->setUser($user);
        $session->processLogin();

        return $session->isLoggedIn();
    }

    /**
     * Retrieve random password
     *
     * @param   int $length
     * @return  string
     */
    public function generatePassword($length = 10)
    {
        $chars = \Magento\Framework\Math\Random::CHARS_LOWERS
            . \Magento\Framework\Math\Random::CHARS_UPPERS
            . \Magento\Framework\Math\Random::CHARS_DIGITS;

        return $password = $this->mathRandom->getRandomString($length, $chars);
    }

    /**
    * Get Magento version
    *
    * @return Magento version
    */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

}
