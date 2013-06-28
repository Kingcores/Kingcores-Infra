<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Dummy;
use Bluefin\Log;

use Symfony\Component\Yaml\Yaml;

/**
 * Builder
 */
class Arsenal
{
    private static $__instance;

    /**
     * @static
     * @return Arsenal
     */
    public static function getInstance()
    {
        if (!isset(self::$__instance))
        {
            self::$__instance = new self();
        }

        return self::$__instance;
    }

    public static function loadLanceFile($fileRelativePath)
    {
        $arsenal = Arsenal::getInstance();

        $arsenal->consoleLogger()->setLogWithoutCR();
        $arsenal->log()->info(
            "Loading '{$fileRelativePath}' ..." ,
            Convention::LOG_CAT_LANCE_CORE);

        $filePath = LANCE . '/' . $fileRelativePath;

        file_exists($filePath) || ($filePath = LANCE_BUILTIN . '/' . $fileRelativePath);

        if (!file_exists($filePath))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("\"{$fileRelativePath}\" not found!");
        }

        $result = Yaml::load($filePath);

        $arsenal->consoleLogger()->setLogMessageOnly();
        $arsenal->log()->info(
            " [OK]\n    -> {$filePath}" ,
            Convention::LOG_CAT_LANCE_CORE);

        return $result;
    }

    private $_schemaSets;
    private $_schemaSetPragmas;
    private $_log;
    private $_consoleLogger;
    private $_locale;

    private $_schemaCache;

    public function __construct()
    {
        $fileRelativePath = '/etc/lance.yml';
        $filePath = LANCE . $fileRelativePath;
        file_exists($filePath) || ($filePath = LANCE_BUILTIN . $fileRelativePath);

        if (!file_exists($filePath))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("\"{$fileRelativePath}\" not found!");
        }

        $lanceConfig = Yaml::load($filePath);

        $timezone = _V('lance.timezone', $lanceConfig, 'Asia/Shanghai');
        date_default_timezone_set($timezone);

        $phpInternalEncoding = _V('lance.phpInternalEncoding', $lanceConfig, 'UTF-8');
        mb_internal_encoding($phpInternalEncoding);

        $locale = _V('lance.locale', $lanceConfig, 'zh_CN');
        setlocale(LC_ALL, $locale);
        $this->_locale = $locale;

        $this->_schemaSets = array();
        $this->_schemaSetPragmas = array();

        $logConfig = array_try_get($lanceConfig, 'log');

        $this->_schemaCache = [];

        if (empty($logConfig))
        {
            $this->_log = new Dummy();
        }
        else
        {
            $this->_log = new Log();

            foreach ($logConfig as $loggerConfig)
            {
                $loggerType = array_try_get($loggerConfig, 'type', 'file', true);

                $loggerClass = "\\Bluefin\\Logger\\" . usw_to_pascal($loggerType) . "Logger";

                /**
                 * @var \Bluefin\Logger\LoggerInterface
                 */
                $logger = new $loggerClass($loggerConfig);

                $this->_log->addLogger($logger);

                if ($loggerType == 'console')
                {
                    $this->_consoleLogger = $logger;
                }
            }
        }
    }

    /**
     * @return \Bluefin\Log
     */
    public function log()
    {
        return $this->_log;
    }

    /**
     * @return \Bluefin\Logger\ConsoleLogger
     */
    public function consoleLogger()
    {
        return isset($this->_consoleLogger) ? $this->_consoleLogger : new Dummy();
    }

    public function locale()
    {
        return $this->_locale;
    }

    public function getSchemaSetPragma($schemaSetName, $pragma, $default = null)
    {
        return array_try_get(array_try_get($this->_schemaSetPragmas, $schemaSetName, array()), $pragma, $default);
    }

    /**
     * @param $schemaName
     * @param null $version
     * @return \Bluefin\Lance\Schema
     * @throws \Bluefin\Lance\Exception\GrammarException
     * @throws \Bluefin\Exception\FileNotFoundException
     */
    public function loadSchema($schemaName, $version = null)
    {
        if (array_key_exists($schemaName, $this->_schemaCache))
        {
            return $this->_schemaCache[$schemaName];
        }

        Arsenal::getInstance()->log()->info(
            "Loading schema '{$schemaName}' ..." ,
            Convention::LOG_CAT_LANCE_CORE);

        $filename = isset($version) ? (LANCE . "/versions/{$version}/{$schemaName}.yml") : (LANCE . "/{$schemaName}.yml");
        if (!file_exists($filename))
        {
            throw new \Bluefin\Exception\FileNotFoundException($filename);
        }

        $config = Yaml::load($filename);
        if (!array_key_exists($schemaName, $config))
        {
            throw new \Bluefin\Lance\Exception\GrammarException(
                "'{$schemaName}' should be the root node of '{$schemaName}.yml'!"
            );
        }

        $schemaConfig = $config[$schemaName];

        return ($this->_schemaCache[$schemaName] = new Schema($schemaName, $schemaConfig));
    }

    public function loadSchemaSet($sourceSite, $schemaSetName)
    {
        if (array_key_exists($schemaSetName, $this->_schemaSets))
        {
            return $this->_schemaSets[$schemaSetName];
        }

        Arsenal::getInstance()->log()->info(
            "Loading schema set file '{$schemaSetName}.yml' ..." ,
            Convention::LOG_CAT_LANCE_CORE);

        $schemaSetFileRelPath = "/schema/{$schemaSetName}.yml";
        $schemaSetFilePath = LANCE . $schemaSetFileRelPath;

        file_exists($schemaSetFilePath) || ($schemaSetFilePath = LANCE_BUILTIN . $schemaSetFileRelPath);

        if (!file_exists($schemaSetFilePath))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Schema set \"{$schemaSetName}\" not found! Source: {$sourceSite}");
        }

        $schemaSetConfig = Yaml::load($schemaSetFilePath);

        $regex = '/^' . Convention::PATTERN_PRAGMA_PREFIX . '(\w[\w-]*)$/';

        $result = array_get_by_reg($schemaSetConfig, $regex, true);

        if (!empty($result))
        {
            $this->_schemaSetPragmas[$schemaSetName] = $result;
        }

        return ($this->_schemaSets[$schemaSetName] = $schemaSetConfig);
    }
}
