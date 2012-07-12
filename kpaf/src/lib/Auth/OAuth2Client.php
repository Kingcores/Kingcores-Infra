<?php

namespace Bluefin\Auth;

use Bluefin\App;
use Bluefin\Request;
use Bluefin\Convention;
use Bluefin\ServiceAgent;
 
class OAuth2Client
{
    private $_service;
    private $_request;
    private $_sessionNamespace;

    public function __construct(Request $request)
    {
        $this->_service = new ServiceAgent('oauth2');
        $this->_request = $request;

        $config = _C('auth.oauth2');
        App::assert(isset($config), 'Missing configuration for "auth.oauth2".');

        $this->_sessionNamespace = array_try_get($this->_config, 'sessionNamespace');
    }

    public function isAuthenticated()
    {
        $accessToken = $this->getAccessTokenParam();

        if (!isset($accessToken))
        {
            $session = new \Zend_Session_Namespace($this->_sessionNamespace, true);
            $accessToken = $session->accessToken;
        }
        else
        {
            $authToken = $this->verifyAccessToken($accessToken);

        }

        if (!isset($accessToken))
        {
            return false;
        }
        
    }

    public function verifyAccessToken()
    {
        
    }

    public function getAccessTokenParam()
    {
        $authHeader = $this->_request->getHttpHeader(OAuth2::AUTHORIZATION_HEADER);

        if (isset($authHeader))
        {
            // Make sure only the auth header is set
            if ($this->_request->has(
                OAuth2::OAUTH2_TOKEN_PARAM_NAME,
                Request::SCOPE_GET | Request::SCOPE_POST
            ))
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _T('Auth token found in GET or POST when token present in header.', Convention::LOCALE_BLUEFIN_DOMAIN)
                );
            }

            $authHeader = trim($authHeader);

            // Make sure it's Token authorization
            if (strcmp(substr($authHeader, 0, 5), "OAuth ") !== 0)
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _T('Auth header found that doesn\'t start with "OAuth".', Convention::LOCALE_BLUEFIN_DOMAIN)
                );
            }

            // Parse the rest of the header
            if (preg_match('/\s*OAuth\s*="(.+)"/', substr($authHeader, 5), $matches) == 0 || count($matches) < 2)
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _T('Malformed auth header.', Convention::LOCALE_BLUEFIN_DOMAIN)
                );
            }

            return $matches[1];
        }

        return $this->_request->get(OAuth2::OAUTH2_TOKEN_PARAM_NAME);
    }
}
