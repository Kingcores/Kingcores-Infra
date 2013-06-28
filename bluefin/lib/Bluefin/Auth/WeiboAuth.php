<?php

namespace Bluefin\Auth;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\Auth\AuthInterface;
use Bluefin\Persistence\PersistenceInterface;

use Common\Helper\WeiboClientFactory;

class WeiboAuth implements AuthInterface
{
    private $_name;
    private $_appName;

    private $_responseUrl;
    private $_defaultRedirect;

    /**
     * @var PersistenceInterface
     */
    private $_persistence;

    public function __construct($authName, array $config)
    {
        if (!all_keys_exists(['responseUrl', 'appName'], $config))
        {
            throw new \Bluefin\Exception\ConfigException("Invalid weibo auth configuration!");
        }

        $this->_name = $authName;
        $this->_appName = $config['appName'];
        $this->_responseUrl = \Bluefin\VarText::parseVarText($config['responseUrl']);
        $this->_defaultRedirect = App::getInstance()->rootUrl();

        $this->_persistence = App::createPersistenceObject(array_try_get($config, 'persistence'));
    }

    public function getAuthUrl($callbackUrl = null, $forceLogin = false, $state = null)
    {
        $c = WeiboClientFactory::createFromConfig('weibotui');
        $o = $c->oauth;

        $url = $o->getAuthorizeURL($this->getResponseUrl($callbackUrl), 'code', $state);

        if ($forceLogin)
        {
            $params = ['forcelogin' => 'true'];
            $url .= '&' . http_build_query($params);
        }

        return $url;
    }

    public function getResponseUrl($redirectUrl = null)
    {
        return isset($redirectUrl) ? build_uri($this->_responseUrl, [Convention::KEYWORD_REQUEST_FROM => $redirectUrl, 'type' => \WBT\Model\Weibotui\WeiboType::WEIBO], null, false) : $this->_responseUrl;
    }

    public function isAuthenticated()
    {
        $identity = $this->getIdentity();
        return isset($identity);
    }

    public function authenticate(array $authInput)
    {
        if (!all_keys_exists(['code', 'from'], $authInput))
        {
            throw new \Bluefin\Exception\InvalidOperationException("Invalid input!");
        }

        $keys = array();
        $keys['code'] = $authInput['code'];
        $keys['redirect_uri'] = $this->getResponseUrl($authInput['from']);

        $c = WeiboClientFactory::createFromConfig('weibotui');
        $o = $c->oauth;

        try
        {
            $token = $o->getAccessToken('code', $keys);
        }
        catch (\OAuthException $e)
        {
            App::getInstance()->log()->error("Failed to get weibo access token by code! Code: {$e->getCode()}, Detail: {$e->getMessage()} Trace: {$e->getTraceAsString()}");

            throw $e;
        }

        return AuthHelper::SUCCESS;
    }

    public function getUniqueID()
    {
        return $this->_persistence->get('uid');
    }

    public function getIdentity()
    {
        return $this->_persistence->get('access_token');
    }

    public function clearIdentity()
    {
        $this->_persistence->clear();
    }

    /**
     * @param string $identity Weibo oauth2 access_token
     * @throws \Bluefin\Exception\ServerErrorException
     * @throws \Bluefin\Exception\ConfigException
     */
    public function setIdentity($identity)
    {
        if (empty($identity) || !all_keys_exists(['access_token', 'uid'], $identity))
        {
            throw new \Bluefin\Exception\ServerErrorException("Invalid '{$this->_name}' identity!");
        }

        $key = "config.weibo.{$this->_appName}";
        $weiboConfig = _C($key);

        if (empty($weiboConfig) || !all_keys_exists(['appKey', 'appSecret'], $weiboConfig))
        {
            throw new \Bluefin\Exception\ConfigException("Invalid weibo client configuration!");
        }

        $data = $identity;
        $data['app_key'] = $weiboConfig['appKey'];
        $data['app_secret'] = $weiboConfig['appSecret'];

        $this->_persistence->reset($data);
        setcookie('weibojs_' . $weiboConfig['appKey'], http_build_query($identity));

        App::getInstance()->session()->append('auth', $this->_name);
    }

    public function refresh()
    {
        App::assert(false);
    }

    public function getData($name = null)
    {
        return $this->_persistence->get($name);
    }

    public function getCaptcha()
    {
        return null;
    }
}
