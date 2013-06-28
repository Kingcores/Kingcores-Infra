<?php

namespace Bluefin\Auth;

use Bluefin\App;
use Bluefin\Data\Model;
use Bluefin\Persistence\PersistenceInterface;
use Bluefin\Convention;

class DbAuth implements AuthInterface
{
    private $_name;

    private $_authUrl;
    private $_responseUrl;

    /**
     * @var Model
     */
    private $_modelClass;
    private $_captchaClass;
    private $_uidColumn;
    private $_identityColumn;
    private $_credentialColumn;

    private $_dataColumns;

    private $_numFailureNeedCaptcha;
    private $_failedTimes = 0;
    private $_needCaptcha = false;

    /**
     * @var PersistenceInterface
     */
    private $_persistence;

    public function __construct($authName, array $config)
    {
        if (!all_keys_exists(['authUrl', 'responseUrl', 'modelClass', 'uidColumn', 'identityColumn', 'credentialColumn'], $config))
        {
            throw new \Bluefin\Exception\ConfigException("Invalid db auth configuration!");
        }

        $this->_name = $authName;
        $this->_authUrl = \Bluefin\VarText::parseVarText($config['authUrl']);
        $this->_responseUrl = \Bluefin\VarText::parseVarText($config['responseUrl']);
        $this->_modelClass = $config['modelClass'];
        $this->_uidColumn = $config['uidColumn'];
        $this->_identityColumn = $config['identityColumn'];
        $this->_credentialColumn = $config['credentialColumn'];

        $this->_numFailureNeedCaptcha = array_try_get($config, 'numFailureNeedCaptcha', -1);

        $this->_dataColumns = array_try_get($config, 'dataColumns', array());
        is_array($this->_dataColumns) || ($this->_dataColumns = array($this->_dataColumns));
        array_push_unique($this->_dataColumns, $this->_uidColumn);
        array_push_unique($this->_dataColumns, $this->_identityColumn);


        $this->_persistence = App::createPersistenceObject(array_try_get($config, 'persistence'));

        if ($this->_numFailureNeedCaptcha != -1 && !$this->isAuthenticated())
        {
            $session = App::getInstance()->session();
            $failedTimesKey = make_dot_name('auth', $this->_name, 'failedTimes');
            $failedTimes = $session->get($failedTimesKey);
            if (isset($failedTimes))
            {
                $this->_failedTimes = $failedTimes;
            }

            if ($failedTimes >= $this->_numFailureNeedCaptcha)
            {
                $this->_needCaptcha = true;
            }
        }
    }

    public function getAuthUrl($callbackUrl = null, $forceLogin = false, $state = null)
    {
        return isset($callbackUrl) ? build_uri($this->_authUrl, array(Convention::KEYWORD_REQUEST_FROM => $callbackUrl)) : $this->_authUrl;
    }

    public function getResponseUrl($redirectUrl = null)
    {
        return isset($redirectUrl) ? build_uri($this->_responseUrl, array(Convention::KEYWORD_REQUEST_FROM => $redirectUrl)) : $this->_responseUrl;
    }

    public function isAuthenticated()
    {
        $identity = $this->getIdentity();
        return isset($identity);
    }

    public function authenticate(array $authInput)
    {
        if (!all_keys_exists(['username', 'password'], $authInput))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _APP_("Invalid parameters.")
            );
        }

        $identity = $authInput['username'];
        $credential = $authInput['password'];

        $condition = array($this->_identityColumn => $identity);

        /**
         * @var Model $model
         */
        $model = new $this->_modelClass($condition);

        if ($model->isEmpty())
        {
            return AuthHelper::FAILURE_IDENTITY_NOT_FOUND;
        }

        /**
         * @var Model $actual
         */
        $actual = new $this->_modelClass();
        $actual->reset($model->data(), true);
        $actual->__set($this->_credentialColumn, $credential);

        $record = $actual->filter();

        if ($model->__get($this->_credentialColumn) != $record[$this->_credentialColumn])
        {
            return AuthHelper::FAILURE_CREDENTIAL_INVALID;
        }

        return AuthHelper::SUCCESS;
    }

    public function getUniqueID()
    {
        return $this->_persistence->get($this->_uidColumn);
    }

    public function getIdentity()
    {
        return $this->_persistence->get($this->_identityColumn);
    }

    /**
     * @param array $identity Identity查询条件
     * @throws \Bluefin\Exception\ServerErrorException
     */
    public function setIdentity($identity)
    {
        $modelClass = $this->_modelClass;
        $data = $modelClass::fetchOneRow($this->_dataColumns, $identity);

        if (empty($data))
        {
            throw new \Bluefin\Exception\ServerErrorException("Invalid '{$this->_name}' identity!");
        }

        $this->_persistence->reset($data);
        App::getInstance()->session()->appendUnique('auth', $this->_name);
    }

    public function clearIdentity()
    {
        $this->_persistence->clear();
    }

    public function refresh()
    {
        $this->setIdentity([$this->_uidColumn => $this->getUniqueID()]);
    }

    public function getData($name = null)
    {
        return $this->_persistence->get($name);
    }

    public function getCaptcha()
    {
        if ($this->_needCaptcha)
        {
            /**
             * @var \Bluefin\Captcha\CaptchaInterface $captcha
             */
            $captcha = new $this->_captchaClass();

            return $captcha->getCaptchaHTML();
        }

        return null;
    }
}
