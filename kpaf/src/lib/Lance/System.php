<?php

namespace Bluefin\Lance;

use Bluefin\App;

class System
{
    public $name;
    public $displayName;
    public $namespace;
    public $locale;

    protected $_systemConfig;
    protected $_installedTins;

    public function __construct($name)
    {
        $this->name = $name;

        $filename = build_path(LANCE, "{$name}.yml");

        if (!file_exists($filename))
        {
            throw new \Bluefin\Exception\FileNotFoundException($filename);
        }

        $config = App::loadYmlFileEx($filename);
        $this->_systemConfig = $config[$this->name];

        $this->_validateConfig();

        $comment = $this->_systemConfig[Convention::KEYWORD_SCHEMA_COMMENT];
        $this->locale = $this->_systemConfig[Convention::KEYWORD_SCHEMA_LOCALE];
        $this->displayName = Convention::getDisplayName($this->locale, $this->name, $this->name, $comment);
        $this->namespace = usw_to_pascal($this->name);

        $this->_dbms = $this->_systemConfig[Convention::KEYWORD_SCHEMA_DB];
        $this->_features = $this->_systemConfig[Convention::KEYWORD_SCHEMA_FEATURES];
        $this->_data = $this->_systemConfig[Convention::KEYWORD_SCHEMA_DATA];

        $dbmsAdapterClass = "\\Bluefin\\Lance\\Adapter\\" . usw_to_pascal($this->getDBMSAdapterName()) . 'Adapter';
        $this->_dbmsAdapter = new $dbmsAdapterClass;


    }

    public function getDBMSType()
    {
        return array_try_get()
    }

    protected function _validateConfig()
    {
        if (empty($this->_systemConfig))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Invalid system config. File: {$filename}");
        }
    }
}
