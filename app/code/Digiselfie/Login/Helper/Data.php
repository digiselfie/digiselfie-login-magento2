<?php
namespace Digiselfie\Login\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Cms\Helper\Page;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Digiselfie Login data Helper
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Data extends AbstractHelper
{

    /**
     * Cookie life time
     */
    const COOKIE_LIFE = 86400;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Cms\Helper\Page
     */
    protected $cmsPageHelper;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Page $cmsPageHelper,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cmsPageHelper = $cmsPageHelper;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        parent::__construct(
            $context
        );
    }

    /**
     * Get data from cookie
     *
     * @return value
     */
    public function getCookie($name) {
        return $this->cookieManager->getCookie($name);
    }

    /**
     * Set data to cookie
     *
     * @param [string] $value    [value of cookie]
     * @param integer $duration [duration for cookie] 1 Day
     *
     * @return void
     */
    public function setCookie($name, $value, $duration = 86400) {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        $this->cookieManager->setPublicCookie($name, $value, $metadata);

    }

    /**
     * delete cookie
     *
     * @return void
     */
    public function deleteCookie($name) {
        $this->cookieManager->deleteCookie(
            $name,
            $this->cookieMetadataFactory
                ->createCookieMetadata()
                ->setPath($this->sessionManager->getCookiePath())
                ->setDomain($this->sessionManager->getCookieDomain())
        );
    }

}
