<?php

namespace Common\Helper;

use OAuth2;
use Bluefin\App;

require_once 'OAuth2/OAuth2.php';

class WBTOAuth2 extends OAuth2
{
    /**
     * @param $url
     * @param $clientId
     * @param $signature
     * @return \WBT\Model\Weibotui\OAuthClient
     * @throws \Bluefin\Exception\RequestException
     */
    public static function verify($url, $clientId, $signature)
    {
        $client = new \WBT\Model\Weibotui\OAuthClient($clientId);

        $url = build_uri($url, ['client_id' => null, 'signature' => null]);

        $url = preg_replace('/^(http|https):\/\//', '', $url);

        $secret = $client->getSecret();

        $expected = md5($url . $secret);

        if ($signature != $expected)
        {
            throw new \Bluefin\Exception\RequestException(_APP_('Invalid signature!'), \Bluefin\Common::HTTP_FORBIDDEN);
        }

        $redirectUri = $client->getRedirectUri();

        /*
        if (isset($redirectUri))
        {
            if (strcasecmp(substr($url, 0, strlen($redirectUri)), $redirectUri) !== 0)
            {
                throw new \Bluefin\Exception\RequestException(_APP_('Invalid redirect_uri!'), \Bluefin\Common::HTTP_FORBIDDEN);
            }
        }
        */

        return $client;
    }

    public static function sign($url, $clientId, $secret)
    {
        $url = build_uri($url, ['client_id' => null, 'signature' => null]);

        $urlTrimmed = preg_replace('/^(http|https):\/\//', '', $url);

        $regSign = md5($urlTrimmed . $secret);

        $params = [
            'client_id' => $clientId,
            'signature' => $regSign
        ];

        return build_uri($url, $params);
    }

    protected function genAccessToken()
    {
        return UniqueIdentity::generate(40);
    }
}
