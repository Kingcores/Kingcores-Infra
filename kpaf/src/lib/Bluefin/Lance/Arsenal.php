<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Log;
use Bluefin\Log\FileLogger;
use Bluefin\Log\ConsoleLogger;

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

    private $_schemaSets;
    private $_schemaSetPragmas;
    private $_log;

    public function __construct()
    {
        date_default_timezone_set('Asia/Shanghai');

        $this->_schemaSets = array();
        $this->_schemaSetPragmas = array();

        $this->_log = new Log();

        $fileLogger = new FileLogger(array(
            'path' => ROOT . '/log',
            'filename' => 'lance_{%`time|date="Ymd"}.log',
            'level' => Log::DEBUG,
            'channels' => array(
                Convention::LOG_CAT_LANCE_CORE => true,
                Convention::LOG_CAT_LANCE_DIAG => true,
            )
        ));

        $consoleLogger = new ConsoleLogger(array(
            'level' => Log::INFO,
            'channels' => array(
                Convention::LOG_CAT_LANCE_CORE => true,
            )
        ));

        $this->_log->addLogger($fileLogger);
        $this->_log->addLogger($consoleLogger);
    }

    /**
     * @return \Bluefin\Log
     */
    public function log()
    {
        return $this->_log;
    }

    public function getSchemaSetPragma($schemaSetName, $pragma, $default = null)
    {
        return array_try_get(array_try_get($this->_schemaSetPragmas, $schemaSetName, array()), $pragma, $default);
    }

    /**
     * @param $schemaName
     * @return Schema
     * @throws Exception\GrammarException
     * @throws \Bluefin\Exception\FileNotFoundException
     */
    public function loadSchema($schemaName)
    {
        Arsenal::getInstance()->log()->info(
            "Loading schema '{$schemaName}' ..." ,
            Convention::LOG_CAT_LANCE_CORE);

        $filename = LANCE . "/{$schemaName}.yml";
        if (!file_exists($filename))
        {
            throw new \Bluefin\Exception\FileNotFoundException($filename);
        }

        $config = App::loadYmlFileEx($filename);
        if (!array_key_exists($schemaName, $config))
        {
            throw new \Bluefin\Lance\Exception\GrammarException(
                "'{$schemaName}' should be the root node of '{$schemaName}.yml'!"
            );
        }

        $schemaConfig = $config[$schemaName];

        return new Schema($schemaName, $schemaConfig);
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
        $schemaSetFilePath = LANCE_EXTEND . $schemaSetFileRelPath;

        file_exists($schemaSetFilePath) || ($schemaSetFilePath = BLUEFIN_BUILTIN . $schemaSetFileRelPath);

        if (!file_exists($schemaSetFilePath))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Schema set \"{$schemaSetName}\" not found! Source: {$sourceSite}");
        }

        $schemaSetConfig = App::loadYmlFileEx($schemaSetFilePath);

        $regex = '/^' . Convention::PATTERN_PRAGMA_PREFIX . '(\w+)$/';

        $result = array_get_by_reg($schemaSetConfig, $regex, true);

        if (!empty($result))
        {
            $this->_schemaSetPragmas[$schemaSetName] = $result;
        }

        return ($this->_schemaSets[$schemaSetName] = $schemaSetConfig);
    }
}
