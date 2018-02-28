<?php
namespace Digiselfie\Login\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Digiselfie\Login\Model\Digiselfie\Client;
use Digiselfie\Login\Block\Login\Provider;


/**
 * Digiselfie Login   login block
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Login extends Provider
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        Template\Context $context,
        Client $loginDigiselfieClient,
        Registry $registry,
        array $data = []
    ) {
        $this->loginDigiselfieClient = $loginDigiselfieClient;
        $this->registry = $registry;
        parent::__construct(
            $context,
            $loginDigiselfieClient,
            $data
        );
    }



    protected function _construct() {
        parent::_construct();

        $this->setTemplate('digiselfie/login/login.phtml');
    }
}
