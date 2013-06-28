<?php

class WBTClient
{
    static $CURL_OPTS = [
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'wbt-oauth2-client',
        CURLOPT_HTTPHEADER => ["Accept: application/json"]
    ];

    const ACCESS_TOKEN_KEY = 'wbt_client:';

    protected $_key;
    protected $_secret;
    protected $_accessToken;
    protected $_apiEntry;

    function __construct($key, $secret)
    {
        $this->_key = $key;
        $this->_secret = $secret;
        $this->_apiEntry = 'http://www.weibotui.com/api/open/';

        $sessionKey = $this->sessionKey();

        if (isset($_SESSION[$sessionKey]))
        {
            $this->_accessToken = $_SESSION[$sessionKey];
        }
    }

    function ssoURL() { return 'http://www.weibotui.com/auth/sso'; }

    function authorizeURL() { return 'http://www.weibotui.com/auth/authorize'; }

    function sessionKey() { return self::ACCESS_TOKEN_KEY . $this->_key; }

    function accessToken($accessToken = null)
    {
        if (isset($accessToken))
        {
            $this->_accessToken = $accessToken;

            $sessionKey = $this->sessionKey();
            $_SESSION[$sessionKey] = $this->_accessToken;
        }

        return $this->_accessToken;
    }

    function forgetAccessToken()
    {
        $sessionKey = $this->sessionKey();
        unset($_SESSION[$sessionKey]);
        $this->_accessToken = null;
    }

    function getAuthorizeURL($redirectUri, $state = null)
    {
        $params = [
            'redirect_uri' => (false === $redirectUri ? 0 : $redirectUri),
            'state' => $state
        ];

        $resultURL = $this->authorizeURL() . '?' . http_build_query($params);
        $urlToSign = preg_replace('/^(http|https):\/\//', '', $resultURL);

        $signature = md5($urlToSign . $this->_secret);

        $params = [
            'client_id' => $this->_key,
            'signature' => $signature
        ];

        return $this->_buildUri($resultURL, $params);
    }

    function singleSignOn($url, $state = null)
    {
        $redirectUrl = $this->getAuthorizeURL(false, $state);
        header('Location: ' . $redirectUrl);
        exit();
    }

    function verifyTicket()
    {
        try
        {
            if (isset($_GET['ticket']))
            {
                return $this->get_user_profile_by_ticket($_GET['ticket']);
            }

            return null;
        }
        catch (\Exception $e)
        {
            throw new WBTApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    function reloadWithoutTicket()
    {
        header('Location: ' . $this->_buildUri($_SERVER['REQUEST_URI'], ['ticket' => null]));
        exit();
    }

    function get_user_profile_by_ticket($ticket)
    {
        $post = [ 'ticket' => $ticket ];

        return $this->_api('user/get_user_profile', $post);
    }

    protected function _authorize()
    {
        return null;
    }

    protected function _get_access_token()
    {
        return null;
    }

    protected function _beAuthorized()
    {
        if (empty($this->_accessToken))
        {
            $code = $this->_authorize();
            $this->_accessToken = $this->_get_access_token($code);
        }

        if (empty($this->_accessToken) || empty($this->_accessToken['access_token']))
        {
            throw new WBTApiException('Internal Server Error', 500);
        }

        $_SESSION[$this->_sessionKey] = $this->_accessToken;
    }

    protected function _api($api, array $params = null, $isRetry = false)
    {
        $this->_beAuthorized();

        isset($params) || ($params = []);
        $params['access_token'] = $this->_accessToken['access_token'];

        try
        {
            $response = $this->_send($this->_apiEntry . $api, null, null, $params);
        }
        catch (WBTApiException $wae)
        {
            if (!$isRetry && !isset($this->_accessToken))
            {
                return $this->_api($api, $params, true);
            }

            throw $wae;
        }

        return json_decode($response, true);
    }

    protected function _send($url, array $extraHeaders = null, array $queryParams = null, array $postParam = null)
    {
        isset($queryParams) || ($url = $this->_buildUri($url, $queryParams));

        $post = null;

        if (isset($postParam))
        {
            $post = http_build_query($postParam);
        }

        //$credential = md5($url . isset($post) ? $post : '' . $this->_secret);

        $opts = self::$CURL_OPTS;
        $opts[CURLOPT_URL] = $url;

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        if (isset($extraHeaders))
        {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $extraHeaders);
        }

        curl_setopt_array($ch, $opts);

        if (isset($post))
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $result = curl_exec($ch);

        if (false === $result)
        {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new WBTApiException($error, $errno);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Split the HTTP response into header and body.
        list($headers, $body) = explode("\r\n\r\n", $result);
        $headers = explode("\r\n", $headers);

        //_DEBUG($headers, 'header');
        //_DEBUG($body, 'body');

        // We catch HTTP/1.1 4xx or HTTP/1.1 5xx error response.
        if (strpos($headers[0], 'HTTP/1.1 4') !== false || strpos($headers[0], 'HTTP/1.1 5') !== false)
        {
            $message = '';

            if (preg_match('/^HTTP\/1.1 ([0-9]{3,3}) (.*)$/', $headers[0], $matches))
            {
                $status = $matches[1];
                $message = $matches[2];
            }

            $errorInHeader = false;

            // In case retrun with WWW-Authenticate replace the description.
            foreach ($headers as $header)
            {
                if (preg_match('/^WWW-Authenticate:.*error_description="(.*?)".*/', $header, $matches))
                {
                    $message .= ': ' . $matches[1];
                    $errorInHeader = true;
                    break;
                }
            }

            if (!$errorInHeader)
            {
                $result = json_decode($body, true);
                if (is_array($result))
                {
                    if (isset($result['error']))
                    {
                        $message .= ': ' . $result['error'];
                    }
                }
                else
                {
                    $message .= ': ' . $result;
                }
            }

            if ($status == 401)
            {
                $this->forgetAccessToken();
            }

            throw new WBTApiException($message, $status);
        }

        return $body;
    }

    protected function _buildUri($uri, array $queryParams = null)
    {
        if (empty($queryParams)) return $uri;

        $parsedUrl = parse_url($uri);

        // Add our params to the parsed uri
        if (isset($queryParams))
        {
            if (isset($parsedUrl["query"]))
            {
                parse_str($parsedUrl["query"], $oldQueries);
                $queryParams = array_merge($oldQueries, $queryParams);
            }

            $parsedUrl["query"] = http_build_query($queryParams);
        }

        // Put humpty dumpty back together
        $url =
          ((isset($parsedUrl["scheme"])) ? $parsedUrl["scheme"] . "://" : "")
          . ((isset($parsedUrl["user"])) ? $parsedUrl["user"] . ((isset($parsedUrl["pass"])) ? ":" . $parsedUrl["pass"] : "") . "@" : "")
          . ((isset($parsedUrl["host"])) ? $parsedUrl["host"] : "")
          . ((isset($parsedUrl["port"])) ? ":" . $parsedUrl["port"] : "")
          . ((isset($parsedUrl["path"])) ? $parsedUrl["path"] : "")
          . ((!empty($parsedUrl["query"])) ? "?" . $parsedUrl["query"] : "")
          . ((!empty($parsedUrl["fragment"])) ? "#" . $parsedUrl["fragment"] : "");

        return $url;
    }
}
