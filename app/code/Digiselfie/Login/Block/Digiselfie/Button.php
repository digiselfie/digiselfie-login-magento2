<?php
namespace Digiselfie\Login\Block\Digiselfie;


/**
 * Digiselfie Login  Digiselfie/Button block
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */


class Button extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Digiselfie\Login\Model\Digiselfie\Client
     */
    protected $loginDigiselfieClient;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $generic;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $frameworkHelperDataHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Digiselfie\Login\Model\Digiselfie\Client $loginDigiselfieClient,
        \Magento\Framework\Session\Generic $generic,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Url\Helper\Data $frameworkHelperDataHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->loginDigiselfieClient = $loginDigiselfieClient;
        $this->generic = $generic;
        $this->customerSession = $customerSession;
        $this->frameworkHelperDataHelper = $frameworkHelperDataHelper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        parent::__construct(
            $context,
            $data
        );


      parent::_construct();

      $this->client = $this->loginDigiselfieClient;

      if(!($this->client->isEnabled())) {
          return;
      }

      // $this->userInfo = Mage::registry('digiselfie_login_userdetails');

      // CSRF protection
      if(!$this->generic->getDigiselfieCsrf() || $this->generic->getDigiselfieCsrf()=='') {
          $csrf = md5(uniqid(rand(), TRUE));
          $this->generic->setDigiselfieCsrf($csrf);
      } else {
          $csrf = $this->generic->getDigiselfieCsrf();
      }

      $this->client->setState($csrf);

      if(!($redirect = $this->customerSession->getBeforeAuthUrl())) {
          $redirect = $this->getCurrentUrl();
      }

      // Redirect uri
      $this->generic->setDigiselfieRedirect($redirect);
    }

    public function getDigiselfieApiKey()
    {
        return $this->scopeConfig->getValue('digiselfie_login/general/digiselfie_api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId());
    }

    public function getCurrentUrl()
    {
        return $this->storeManager->getStore()->getCurrentUrl();
    }


    /*
    * @param int|string|null|bool|\Magento\Store\Api\Data\StoreInterface $store [optional]
    * @return bool
    */
    function isAdmin($store = null) {
        /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\App\State $state */
        $state =  $om->get('Magento\Framework\App\State');
        return 'adminhtml' === $state->getAreaCode();
    }
}
