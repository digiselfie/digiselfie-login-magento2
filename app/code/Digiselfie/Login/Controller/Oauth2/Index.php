<?php
namespace Digiselfie\Login\Controller\Oauth2;


/**
 * Digiselfie Login Digiselfie Controller
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Digiselfie\Login\Model\Digiselfie\Client
     */
    protected $loginDigiselfieClient;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Digiselfie\Login\Model\Digiselfie\Client $loginDigiselfieClient ) {
        $this->loginDigiselfieClient = $loginDigiselfieClient;
        parent::__construct(
            $context
        );
    }


    public function execute()
    {
        $this->client = $this->loginDigiselfieClient;

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl( $this->client->createAuthUrl() );
        return $resultRedirect;
    }

}
