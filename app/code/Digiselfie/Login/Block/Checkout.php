<?php
namespace Digiselfie\Login\Block;


/**
 * Digiselfie Login     checkout block
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Checkout extends \Magento\Framework\View\Element\Template
{
    protected $clientDigiselfie = null;

    protected $numEnabled = 0;
    protected $numShown = 0;

    /**
     * @var \Digiselfie\Login\Model\Digiselfie\Client
     */
    protected $loginDigiselfieClient;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Digiselfie\Login\Model\Digiselfie\Client $loginDigiselfieClient,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->loginDigiselfieClient = $loginDigiselfieClient;
        $this->registry = $registry;
        parent::__construct(
            $context,
            $data
        );
    }


    protected function _construct() {
        parent::_construct();

        $this->clientDigiselfie = $this->loginDigiselfieClient;

        if( !$this->digiselfieEnabled() )
            return;

        if($this->digiselfieEnabled()) {
            $this->numEnabled++;
        }

        $this->registry->register('digiselfie_login_button_text', $this->__('Login'));

        $this->setTemplate('digiselfie/login/checkout.phtml');
    }

    protected function getColSet()
    {
        return 'col'.$this->numEnabled.'-set';
    }

    protected function getCol()
    {
        return 'col-'.++$this->numShown;
    }

    protected function digiselfieEnabled()
    {
        return $this->clientDigiselfie->isEnabled();
    }

}
