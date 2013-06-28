<?php

require_once 'WBTClient.php';
require_once 'WBTApiException.php';

/**
 * Internal use only.
 */
class WBTPrivateClient extends WBTClient
{
    private $_clientEntry;

    public function __construct($key, $secret, $localTest = false)
    {
        parent::__construct($key, $secret);

        $this->_clientEntry = 'https://www.weibotui.com/api/open/';

        if ($localTest)
        {
            $this->_apiEntry = 'http://127.0.0.1/api/open/';
            $this->_clientEntry = 'https://127.0.0.1/api/open/';
        }
    }

    public function get_user_token()
    {
        $this->_beAuthorized();
        return $this->_accessToken['user_token'];
    }

    public function client_login($userId)
    {
        $post = [ 'user_id' => $userId ];

        return $this->_api('user/client_login', $post);
    }

    public function payment_get_status($type, $bill_id)
    {
        $post = [ 'type' => $type, 'bill_id'  => $bill_id ];

        return $this->_api('payment/get_status', $post);
    }

    protected function _get_client_token()
    {
        $userPass = base64_encode($this->_key . ':' . $this->_secret);

        $header = [
            "Authorization: Basic {$userPass}",
            "Content-Type: application/x-www-form-urlencoded"
        ];

        $result = $this->_send($this->_clientEntry . 'oauth/client', $header, null, [ 'grant_type' => 'client_credentials', 'scope' => 'sso sna payment' ]);

        return json_decode($result, true);
    }

    protected function _beAuthorized()
    {
        if (empty($this->_accessToken) || (isset($this->_accessToken['expires']) && time() > $this->_accessToken['expires']))
        {
            error_log(json_encode($this->_accessToken));
            $this->accessToken($this->_get_client_token());
            $this->_accessToken['expires'] = $this->_accessToken['expires_in'] + time() - 60;
        }

        if (empty($this->_accessToken) || empty($this->_accessToken['access_token']))
        {
            throw new WBTApiException('Internal Server Error', 500);
        }
    }
}