<?php

namespace Common\Helper;

use Bluefin\Service;
use OAuth2ServerException;

class OAuthService extends Service
{
    protected function _requireOAuthToken($scope = null)
    {
        $oauth = OAuthByClient::createHandler();

        try
        {
            $accsssToken = $oauth->getBearerToken();
            return $oauth->verifyAccessToken($accsssToken, $scope);
        }
        catch (OAuth2ServerException $oauthError)
        {
        	throw new \Bluefin\Exception\RequestException($oauthError->getDescription(), $oauthError->getHttpCode(), $oauthError);
        }
    }
}