<?php

use Bluefin\App;
use Common\Helper\OAuthByClient;

class OAuthService extends \Common\Helper\OAuthService
{
    public function client()
    {
        $oauth = OAuthByClient::createHandler();

        try
        {
        	$oauth->grantAccessToken();
        }
        catch (OAuth2ServerException $oauthError)
        {
        	$oauthError->sendHttpResponse();
        }
        catch (\Bluefin\Exception\BluefinException $be)
        {
            $be->sendHttpResponse();
        }
    }
}