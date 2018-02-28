<?php
namespace Digiselfie\Login\Model\Digiselfie;


/**
 * Digiselfie Login  digiselfie/Client Model
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */


class Client

{
    const XML_REDIRECT_URI_ROUTE = 'digiselfie_login/general/redirect_uri_route';
    const XML_PATH_ENABLED = 'digiselfie_login/general/enabled';
    const XML_PATH_CLIENT_ID = 'digiselfie_login/general/oauth_client_id';
    const XML_PATH_CLIENT_SECRET = 'digiselfie_login/general/oauth_secret';

    const OAUTH2_REVOKE_URI = 'https://www.digiselfie.com/api/v1.0/claim';
    const OAUTH2_CLAIM_URI = 'https://www.digiselfie.com/api/v1.0/claim';
    const OAUTH2_TOKEN_URI = 'https://www.digiselfie.com/oauth2/token';
    const OAUTH2_AUTH_URI = 'https://www.digiselfie.com/oauth2/auth';
    const OAUTH2_SERVICE_URI = 'https://www.digiselfie.com/api/v1.0/consent';

    protected $isEnabled = null;
    protected $clientId = null;
    protected $clientSecret = null;
    protected $redirectUri = null;
    protected $state = '';
    protected $scope = array(
        'offline',
        'openid',
    );

    protected $access = 'offline';
    protected $prompt = 'consent';
    protected $token = null;

    /**
     * @var \Magento\Framework\UrlInterfaceFactory
     */
    protected $urlInterfaceFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;


    public function __construct(
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        $params = array())
    {
        $this->urlInterfaceFactory = $urlInterfaceFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;


        if(($this->isEnabled = $this->_isEnabled())) {
            $this->clientId = $this->_getClientId();
            $this->clientSecret = $this->_getClientSecret();
            $this->redirectUri = $this->urlInterfaceFactory->create()->sessionUrlVar($this->_getRedirectUriRoute());

            if(!empty($params['scope'])) {
                $this->scope = $params['scope'];
            }

            if(!empty($params['state'])) {
                $this->state = $params['state'];
            }

            if(!empty($params['access'])) {
                $this->access = $params['access'];
            }

            if(!empty($params['prompt'])) {
                $this->prompt = $params['prompt'];
            }
        }
    }

    public function isEnabled()
    {
        return (bool) $this->isEnabled;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getAccess()
    {
        return $this->access;
    }

    public function setAccess($access)
    {
        $this->access = $access;
    }

    public function getPrompt()
    {
        return $this->prompt;
    }

    public function setPrompt($prompt)
    {
        $this->access = $prompt;
    }

    public function setAccessToken($token)
    {
        $this->token = json_decode($token);
    }

    public function getAccessToken()
    {
        if(empty($this->token)) {
            $this->fetchAccessToken();
        } else if($this->isAccessTokenExpired()) {
            $this->refreshAccessToken();
        }
        return json_encode($this->token);
    }

    public function createAuthUrl()
    {
        $url =
        self::OAUTH2_AUTH_URI.'?'.
            http_build_query(
                array(
                    'response_type' => 'code',
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->clientId,
                    'scope' => implode(' ', $this->scope),
                    'state' => $_REQUEST['token'],
                    // 'access_type' => $this->access,
                    'prompt' => $this->prompt
                    )
            );
        return $url;
    }


    public function api($method = 'GET', $params = array())
    {
        if(empty($_REQUEST['code'])) {
            throw new \Exception(
                __('Unable to retrieve access code.')
            );
        }

        $response = $this->_httpRequest(
            self::OAUTH2_CLAIM_URI,
            'GET',
            array(
                'code' => $_REQUEST['code'],
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code'
            )
        );

        $response->created = time();
        return $response;
    }

    public function revokeToken()
    {
        if(empty($this->token)) {
            throw new \Exception(
                __('No access token available.')
            );
        }

        if(empty($this->token->refresh_token)) {
            throw new \Exception(
                __('No refresh token, nothing to revoke.')
            );
        }

        $this->_httpRequest(
            self::OAUTH2_CLAIM_URI,
            'POST',
           array(
               'token' => $this->token->refresh_token
           )
        );
    }

    protected function fetchAccessToken()
    {
        if(empty($_REQUEST['code'])) {
            throw new \Exception(
                __('Unable to retrieve access code.')
            );
        }

        $response = $this->_httpRequest(
            self::OAUTH2_CLAIM_URI,
            'GET',
            array(
                'code' => $_REQUEST['code'],
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code'
            )
        );

        $response->created = time();
        $this->token = $response;
    }

    protected function refreshAccessToken()
    {
        if(empty($this->token->refresh_token)) {
            throw new \Exception(
                __('No refresh token, unable to refresh access token.')
            );
        }

        $response = $this->_httpRequest(
            self::OAUTH2_TOKEN_URI,
            'GET',
            array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->token->refresh_token,
                'grant_type' => 'refresh_token'
            )
        );

        $this->token->access_token = $response->access_token;
        $this->token->expires_in = $response->expires_in;
        $this->token->created = time();
    }

    protected function isAccessTokenExpired() {
        // If the token is set to expire in the next 30 seconds.
        $expired = ($this->token->created + ($this->token->expires_in - 30)) < time();
        return $expired;
    }

    protected function _httpRequest($url, $method = 'GET', $params = array())
    {
        $client = new \Zend_Http_Client($url, array('timeout' => 60));
        switch ($method) {
            case 'GET':
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                break;
            default:
                throw new \Exception(
                    __('Required HTTP method is not supported.')
                );
        }


        $response = $client->setHeaders('Authorization', 'Bearer ' . $params['code'])->request($method);

        $decoded_response = json_decode($response->getBody());
        if($response->isError()) {
            $status = $response->getStatus();
            if(($status == 400 || $status == 401)) {
                if(isset($decoded_response->error->message)) {
                    $message = $decoded_response->error->message;
                } else {
                    $message = __('Unspecified OAuth error occurred.');
                }
                throw new \Exception($message);
            } else {
                $message = sprintf(
                    __('HTTP error %d occurred while issuing request.'),
                    $status
                );
                throw new \Exception($message);
            }
        }
        return $decoded_response;
    }

    protected function _isEnabled()
    {
        return $this->_getStoreConfig(self::XML_PATH_ENABLED);
    }

    protected function _getClientId()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_ID);
    }

    protected function _getClientSecret()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_SECRET);
    }

    protected function _getStoreConfig($xmlPath)
    {
        return $this->scopeConfig->getValue($xmlPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId());
    }

    protected function _getRedirectUriRoute()
    {
        return $this->_getStoreConfig(self::XML_REDIRECT_URI_ROUTE);
    }

}