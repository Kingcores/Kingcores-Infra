<?php

namespace Bluefin\Auth;

use Bluefin\Request;
 
class OAuth2 extends AbstractAuth 
{
    const AUTHORIZATION_HEADER = 'AUTHORIZATION';

    const OPTION_KEYWORD_REDIRECT_URI = 'redirectUri';

    const E_ACCESS_TOKEN_NO_ERROR = 0;
    const E_ACCESS_TOKEN_NOT_PRESENT = -1;
    const E_ACCESS_TOKEN_INVALID = -2;
    const E_ACCESS_TOKEN_EXPIRED = -3;
    const E_ACCESS_TOKEN_ACCESS_DENIED = -4;
    const E_ACCESS_TOKEN_OPERATION_DENIED = -5;

    const OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE = 'code';

    /**
     * Denotes "authorization_code" grant types (for token obtaining).
     */
    const OAUTH2_GRANT_TYPE_AUTH_CODE = "authorization_code";

    /**
     * Denotes "password" grant types (for token obtaining).
     */
    const OAUTH2_GRANT_TYPE_USER_CREDENTIALS = "password";

    /**
     * Denotes "assertion" grant types (for token obtaining).
     */
    const OAUTH2_GRANT_TYPE_ASSERTION = "assertion";

    /**
     * Denotes "refresh_token" grant types (for token obtaining).
     */
    const OAUTH2_GRANT_TYPE_REFRESH_TOKEN = "refresh_token";

    /**
     * Used to define the name of the OAuth access token parameter (POST/GET/etc.).
     *
     * IETF Draft sections 5.1.2 and 5.1.3 specify that it should be called
     * "oauth_token" but other implementations use things like "access_token".
     *
     * I won't be heartbroken if you change it, but it might be better to adhere
     * to the spec.
     *
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.1.2
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.1.3
     */
    const OAUTH2_TOKEN_PARAM_NAME = "oauth_token";

    const TOKEN_KEYWORD_EXPIRES = 'expires';

    public function __construct(Request $request)
    {
        parent::__construct($request, 'oauth');
    }

    public function getAuthURI()
    {
        if (!array_key_exists(self::OPTION_KEYWORD_REDIRECT_URI, $this->_options))
        {
            throw new \Bluefin\Exception\ConfigException('Missing required auth option: ' . self::OPTION_KEYWORD_REDIRECT_URI);
        }

        //$baseURI = $this->_options[self::OPTION_KEYWORD_REDIRECT_URI];



        //return build_uri();
    }

    public function isAuthenticated()
    {
        $errorCode = $this->_verifyAccessToken();
        if (self::E_ACCESS_TOKEN_NO_ERROR === $errorCode) return true;

        return false;
    }

    public function isAuthorized($resource = null, $operation = null)
    {
        // Check resource & operation acl, if provided
        //TODO:


        return false;
    }

    public function handleUnauthorizedAccess()
    {

    }

    private function _verifyAccessToken()
    {
        $token_param = $this->_getAccessTokenParams();

        // Access token was not provided
        if (false === $token_param)
        {
            return self::E_ACCESS_TOKEN_NOT_PRESENT;
        }

        // Get the stored token data (from the implementing subclass)
        $token = $this->_getAccessToken($token_param);
        if (is_null($token))
        {
            return self::E_ACCESS_TOKEN_INVALID;
        }

        // Check token expiration
        if (isset($token[self::TOKEN_KEYWORD_EXPIRES]) && time() > $token[self::TOKEN_KEYWORD_EXPIRES])
        {
            return self::E_ACCESS_TOKEN_EXPIRED;
        }

        return true;
    }

    private function _getAccessTokenParams()
    {
        
    }

    private function _getAccessToken($tokenParam)
    {

    }
}
