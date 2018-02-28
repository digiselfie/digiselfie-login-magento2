<?php
namespace Digiselfie\Login\Model\Digiselfie;


/**
 * Digiselfie Login   digiselfie/Userdetails Model
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Userdetails
{
    protected $client = null;
    protected $userInfo = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Digiselfie\Login\Model\Digiselfie\Client
     */
    protected $loginDigiselfieClient;

    /**
     * @var \Digiselfie\Login\Helper\Digiselfie
     */
    protected $loginDigiselfieHelper;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $generic;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Digiselfie\Login\Model\Digiselfie\Client $loginDigiselfieClient,
        \Digiselfie\Login\Helper\Digiselfie $loginDigiselfieHelper,
        \Magento\Framework\Session\Generic $generic
    ) {
        $this->customerSession = $customerSession;
        $this->loginDigiselfieClient = $loginDigiselfieClient;
        $this->loginDigiselfieHelper = $loginDigiselfieHelper;
        $this->generic = $generic;
        if(!$this->customerSession->isLoggedIn())
            return;
        $this->client = $this->loginDigiselfieClient;
        if(!($this->client->isEnabled())) {
            return;
        }

        $customer = $this->customerSession->getCustomer();
        if(($digiselfieLoginId = $customer->getDigiselfieLoginId()) &&
                ($digiselfieLoginToken = $customer->getDigiselfieLoginToken())) {
            $helper = $this->loginDigiselfieHelper;
            try{
                $this->client->setAccessToken($digiselfieLoginToken);
                $this->userInfo = $this->client->api('/userinfo');
                /* The access token may have been updated automatically due to
                 * access type 'offline' */
                $customer->setDigiselfieLoginToken($this->client->getAccessToken());
                $customer->save();
            } catch(Digiselfie_Login_OAuthException $e) {
                $helper->disconnect($customer);
                $this->generic->addNotice($e->getMessage());
            } catch(Exception $e) {
                $helper->disconnect($customer);
                $this->generic->addError($e->getMessage());
            }
        }
    }

    public function getUserDetails()
    {
        return $this->userInfo;
    }
}
