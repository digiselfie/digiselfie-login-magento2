<?php
namespace Digiselfie\Login\Block\Login;

use Magento\Framework\View\Element\Template;
use Digiselfie\Login\Model\Digiselfie\Client;

class Provider extends Template
{
    protected $clientDigiselfie = null;

    protected $numEnabled = 0;
    protected $numDescShown = 0;
    protected $numButtShown = 0;

    /**
     * @var \Digiselfie\Login\Model\Digiselfie\Client
     */
    protected $loginDigiselfieClient;

    public function __construct(
        Template\Context $context,
        Client $loginDigiselfieClient,
        array $data = []
    ) {
        $this->loginDigiselfieClient = $loginDigiselfieClient;
        parent::__construct(
            $context,
            $data
        );
    }


    protected function _construct()
    {
        parent::_construct();

        $this->clientDigiselfie = $this->loginDigiselfieClient;

        if( !$this->digiselfieEnabled() )
            return;

        if($this->digiselfieEnabled()) {
            $this->numEnabled++;
        }
    }

    public function getColSet()
    {
        return 'col'.$this->numEnabled.'-set';
    }

    public function getButtCol()
    {
        return 'col-'.++$this->numButtShown;
    }

    public function digiselfieEnabled()
    {
        return $this->clientDigiselfie->isEnabled();
    }

}