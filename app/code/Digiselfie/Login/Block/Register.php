<?php
namespace Digiselfie\Login\Block;


/**
 * Digiselfie Login  register block
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Register extends \Magento\Framework\View\Element\Template
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

        $this->setTemplate('digiselfie/login/register.phtml');
    }

    public function getColSet()
    {
        return 'col'.$this->numEnabled.'-set';
    }

    public function getCol()
    {
        return 'col-'.++$this->numShown;
    }

    public function digiselfieEnabled()
    {
        return $this->clientDigiselfie->isEnabled();
    }

}
