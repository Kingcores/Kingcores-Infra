<?php

namespace Bluefin;

use Bluefin\App;
 
class ServiceAgent
{
    private $_baseUrl;
    private $_client;

    public function __construct($id)
    {
        $cfgItemName = make_dot_name('service', $id);
        $config = _C($cfgItemName);
        if (!isset($config) || !array_key_exists('baseUrl', $config))
        {
            throw new \Bluefin\Exception\ConfigException("Invalid configuration item: {$cfgItemName}");
        }

        $this->_baseUrl = rtrim($config['baseUrl'], '/');
        $params = array_try_get($config, 'params', array());

        $this->_client = new \Zend_Http_Client();
        $this->_client->setConfig($params);
    }

    public function invoke($relativePath, array $postParams = null)
    {
        $this->_client->resetParameters();
        $this->_client->setUri($this->_baseUrl . str_pad_if($relativePath, '/', true, false));
        if (isset($postParams))
        {
            $this->_client->setParameterPost($postParams);
        }
        return $this->_client->request(\Zend_Http_Client::POST);
    }
}
