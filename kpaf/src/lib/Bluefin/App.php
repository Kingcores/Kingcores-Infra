<?php

namespace Bluefin;

use Bluefin\Yaml\Yaml;
use Bluefin\Log\Log;

class App
{    
    private static $_instance;

    /**
     * @static
     * @return App
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance))
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 断言
     * @static
     * @throws Exception\AssertException
     * @param  $expression 布尔表达式
     * @param string $extraMessage 额外错误信息
     * @return void
     */
    public static function assert($expression, $extraMessage = null)
    {
        if (ASSERT_BEHAVIOR != 'disable' && !$expression)
        {
            $traces = debug_backtrace();
            $cause = $traces[0];
            $message = "Assertion failed at line({$cause['line']}) in file \"{$cause['file']}\"!";
            if (isset($extraMessage))
            {
                $message .= " {$extraMessage}";
            }

            if (ASSERT_BEHAVIOR == 'throw')
            {
                throw new \Bluefin\Exception\AssertException($message);
            }

            if (ASSERT_BEHAVIOR == 'error')
            {
                trigger_error($message, E_USER_ERROR);
            }

            //ASSERT_BEHAVIOR == 'ignore'
            App::getInstance()->log()->err($message);
        }
    }

    /**
     * @static
     * @param $ymlFile
     * @return array
     */
    public static function loadYmlFileEx($ymlFile)
    {
        $yml = Yaml::load($ymlFile);
        if (is_array($yml)) self::expandYml($yml);
        return $yml;
    }

    public static function expandYml(array &$yml)
    {
        foreach ($yml as $key => &$config)
        {
            if (Convention::CONFIG_KEYWORD_INCLUDE === $key)
            {
                unset($yml[Convention::CONFIG_KEYWORD_INCLUDE]);

                if (is_array($config))
                {                   
                    foreach ($config as $includeFile)
                    {
                        if (!file_exists($includeFile))
                        {
                            error_log("File \"{$includeFile}\" included in the configuration does not exist.");
                            continue;
                        }

                        $loaded = self::loadYmlFileEx($includeFile);
                        $yml = array_merge($yml, is_array($loaded) ? $loaded : array($loaded));
                    }
                }
                else
                {
                    if (!file_exists($config))
                    {
                        error_log("File \"{$config}\" included in the configuration does not exist.");
                        continue;
                    }

                    $yml = array_merge($yml, self::loadYmlFileEx($config));
                }
            }
            else if (is_array($config))
            {
                self::expandYml($config);
            }
        }
    }

    private $_startTime;

    private $_config;
    private $_bathPath;

    private $_log = array();
    private $_dbAdapters = array();
    private $_auth = array();

    private $_request;
    private $_response;

    private $_currentLocale;
    private $_localeText;
    private $_localePath;

    private $_cache = array();
    private $_registry = array();

    private function __construct()
    {
    }

    public function __destruct()
    {
    }

    public function __clone()
    {
        App::assert(false, Convention::MSG_METHOD_NOT_ALLOWED);
    }

    public function __wakeup()
    {
        App::assert(false, Convention::MSG_METHOD_NOT_ALLOWED);
    }

    /**
     * @return App
     */
    public function bootstrap()
    {
        // 设置启动时间用于调试
        $this->_startTime = microtime(true);

        // 读取配置文件
        $this->_loadConfig();

        // 应用配置
        $this->_applyAppSettings();

        if (!defined('STDOUT'))
        {
            // 如果不是CLI模式，启动Session
            $this->_startSession();
        }

        // 初始化多语言配置
        $this->_initializeLocale();

        return $this;
    }

    public function startGateway()
    {
        $gateway = new Gateway();
        $this->setRegistry('gateway', $this);

        $gateway->service();
    }

    /**
     * @return int
     */
    public function startTime()
    {
        return $this->_startTime;
    }

    public function elapsedTime()
    {
        $currentTime = microtime(true);

        return $currentTime - $this->_startTime;
    }

    public function config($section = null)
    {
        return isset($section) ? array_try_get($this->_config, $section) : $this->_config;
    }

    public function basePath()
    {
        return $this->_bathPath;
    }

    /**
     * Gets a logger.
     *
     * @param string $id
     * @return Log
     */
    public function log($id = Convention::CONFIG_KEYWORD_DEFAULT)
    {        
        //如果日志对象还不存在
        if (!isset($this->_log[$id]))
        {
            if (array_key_exists(Convention::CONFIG_SECTION_LOG, $this->_config))
            {
                $logSection = $this->_config[Convention::CONFIG_SECTION_LOG];

                App::assert(
                    array_key_exists($id, $logSection),
                    "Config item for log[{$id}] is not found!"
                );

                //根据标识读取配置
                $loggersConfig = $logSection[$id];
                is_array($loggersConfig) || ($loggersConfig = array($loggersConfig));

                //创建日志对象
                $log = new Log();

                foreach ($loggersConfig as $loggerConfig)
                {
                    $loggerType = array_try_get($loggerConfig, 'type', 'file', true);

                    $loggerClass = "\\Bluefin\\Log\\" . usw_to_pascal($loggerType) . "Logger";

                    /**
                     * @var \Bluefin\Log\LoggerInterface
                     */
                    $logger = new $loggerClass($loggerConfig);

                    $log->addLogger($logger);
                }
            }
            else
            {
                $log = Dummy::getInstance();
            }

            $this->_log[$id] = $log;
        }

        return $this->_log[$id];
    }

    public function addTranslation($domain, $message, $translation)
    {
        if (!isset($this->_localeText)) return;

        if (!array_key_exists($domain, $this->_localeText))
        {
            $this->_localeText[$domain] = array();
        }

        $domainText = &$this->_localeText[$domain];
        $domainText[$message] = $translation;

        if (ENABLE_LOCALE_EXPORT)
        {
            $this->_exportLocale($domain);
        }
    }

    public function translate($message, $domain)
    {
        if (!isset($this->_localeText)) return $message;

        if (array_key_exists($domain, $this->_localeText))
        {
            $domainText = &$this->_localeText[$domain];
            App::Assert(is_array($domainText), "Unknown locale domain: {$domain}, or maybe in wrong encoding.");

            if (array_key_exists($message, $domainText) && $domainText[$message] != '')
            {
                return $domainText[$message];
            }
            else
            {
                $domainText[$message] = '';
            }
        }
        else
        {
            $this->_localeText[$domain] = array($message => '');
        }

        if (ENABLE_LOCALE_EXPORT)
        {
            $this->_exportLocale($domain);
        }

        return $message;
    }

    /**
     * @param $id
     * @return \Zend_Db_Adapter_Abstract
     */
    public function db($id)
    {
        App::assert(
            array_key_exists(Convention::CONFIG_SECTION_DB, $this->_config),
            "Config item for db is not found!"
        );

        //如果数据库对象还不存在
        if (!isset($this->_dbAdapters[$id]))
        {
            $dbSection = $this->_config[Convention::CONFIG_SECTION_DB];

            App::assert(
                array_key_exists($id, $dbSection),
                "Config item for db[{$id}] is not found!"
            );

            //根据标识读取配置
            $dbConfig = $dbSection[$id];

            $this->_dbAdapters[$id] = Zend_Db::factory($dbConfig['adapter'], $dbConfig['params']);

            //TODO: check charset
            //$database = "\\{$dbConfig['namespace']}\\Model\\" . usw_to_pascal($id) . "Database";
            //$database::getInstance()->init();
        }

        return $this->_dbAdapters[$id];
    }

    public function cache($id)
    {
        App::assert(
            array_key_exists(Convention::CONFIG_SECTION_CACHE, $this->_config),
            "Config item for cache is not found!"
        );

        //如果缓存对象还不存在
        if (!isset($this->_cache[$id]))
        {
            $cacheSection = $this->_config[Convention::CONFIG_SECTION_CACHE];

            App::assert(
                array_key_exists($id, $cacheSection),
                "Config item for cache[{$id}] is not found!"
            );

            //根据标识读取配置
            $cacheConf = $cacheSection[$id];
            $cacheStorageEngine = $cacheConf['storage'];

            if ($cacheStorageEngine == 'Redis')
            {
                require_once 'predis/Predis.php';
                $this->_cache[$id] = new \Predis_Client($cacheConf['params'], isset($cacheConf['client']) ? $cacheConf['client'] : null);
            }
            else
            {
                //TODO: add other cache system support
                throw new \Bluefin\Exception\ConfigException("Unsupported cache storage engine: {$cacheStorageEngine}");
            }
        }

        return $this->_cache[$id];
    }

    public function currentLocale()
    {
        return $this->_currentLocale;
    }

    /**
     * @param $authName
     * @return \Bluefin\Auth\AuthInterface
     */
    public function auth($authName)
    {
        if (!isset($this->_auth[$authName]))
        {
            $handler = _C("auth.{$authName}.authHandler");
            if (is_null($handler)) return null;

            $this->_auth[$authName] = new $handler();
        }

        return $this->_auth[$authName];
    }

    /**
     * @return \Bluefin\Request
     */
    public function request()
    {
        //未创建Request对象，则创建Request对象
        if (!isset($this->_request))
        {
            $this->_request = Request::createFromGlobals();
        }

        return $this->_request;
    }

    /**
     * @return \Bluefin\Response
     */
    public function response()
    {
        //未创建Response对象，则创建Response对象
        if (!isset($this->_response))
        {
            $this->_response = new Response();
        }

        return $this->_response;
    }

    public function inRegistry($name)
    {
        return array_key_exists($name, $this->_registry);
    }

    public function getRegistry($name)
    {
        return array_try_get($this->_registry, $name);
    }

    public function setRegistry($name, $data)
    {
        $this->_registry[$name] = $data;
    }

    private function _loadConfig()
    {
        // 建立缓存目录
        ensure_dir_exist(CACHE, 0750);

        $globalConfigFile = CACHE . '/global.php';

        if (ENABLE_CACHE && file_exists($globalConfigFile))
        {
            // 启用缓存，而且配置文件的缓存存在
            $this->_config = require $globalConfigFile;
        }
        else
        {
            $rawConfigFile = APP_ETC . '/global.' . ENV . '.yml';
            if (file_exists($rawConfigFile))
            {
                $this->_config = self::loadYmlFileEx($rawConfigFile);
            }
            else
            {
                $rawConfigFile = APP_ETC . '/global.yml';
                if (file_exists($rawConfigFile))
                {
                    $this->_config = self::loadYmlFileEx($rawConfigFile);
                }
                else
                {
                    $this->_config = array();
                }
            }

            if (ENABLE_CACHE)
            {
                // 如果启用缓存，则生成缓存
                save_var_to_php($globalConfigFile, $this->_config);
            }
        }
    }

    private function _applyAppSettings()
    {
        if (!array_key_exists(Convention::CONFIG_SECTION_APP, $this->_config)) return;

        $appSection = $this->_config[Convention::CONFIG_SECTION_APP];

        if (array_key_exists('timezone', $appSection))
        {
            date_default_timezone_set($appSection['timezone']);
        }

        if (array_key_exists('phpInternalEncoding', $appSection))
        {
            if (extension_loaded('mbstring'))
            {
                mb_internal_encoding($appSection['phpInternalEncoding']);
            }
            else
            {
                throw new \Bluefin\Exception\ServerErrorException("PHP extension 'mb_string' has not been loaded.");
            }
        }

        if (array_key_exists('requestOrder', $appSection))
        {
            $this->request()->setRequestOrder($appSection['requestOrder']);
        }

        if (array_key_exists('basePath', $appSection))
        {
            $this->_bathPath = $appSection['basePath'];
        }
        else
        {
            $this->_bathPath = '/';
        }

        $this->log()->info("--------------------------------------------------------------------------------");
    }

    private function _startSession()
    {
        if (array_key_exists(Convention::CONFIG_SECTION_SESSION, $this->_config))
        {
            $sessionSection = $this->_config[Convention::CONFIG_SECTION_SESSION];

            //判断配置文件是否提供SessionSaveHandler类名
            if (isset($sessionSection['saveHandler']))
            {
                $saveHandler = new $sessionSection['saveHandler']($sessionSection['params']);

                session_set_save_handler(
                            array($saveHandler, 'open'),
                            array($saveHandler, 'close'),
                            array($saveHandler, 'read'),
                            array($saveHandler, 'write'),
                            array($saveHandler, 'destroy'),
                            array($saveHandler, 'gc')
                        );
            }
        }

        //Hack for Flash Post
		if ($_SERVER['HTTP_USER_AGENT'] == 'Shockwave Flash' && isset($_POST['PHPSESSID']))
        {
            session_id($_POST['PHPSESSID']);            
            unset($_POST['PHPSESSID']);            
        }

        session_start();

        if (!isset($_SESSION[Convention::SESSION_LIFE_COUNTER]))
        {
            session_regenerate_id(true);
            $_SESSION[Convention::SESSION_LIFE_COUNTER] = 0;
        }
        else
        {
            $_SESSION[Convention::SESSION_LIFE_COUNTER]++;
        }
    }

    private function _initializeLocale()
    {
        //TODO: 修改为按需加载

        if (!array_key_exists(Convention::CONFIG_SECTION_LOCALE, $this->_config)) return;

        $localeSection = $this->_config[Convention::CONFIG_SECTION_LOCALE];

        $localeParameter = array_try_get($localeSection, 'requestName', Convention::DEFAULT_LOCALE_REQUEST_NAME);
        $supportedLocales = array_try_get($localeSection, 'supportedLocales', array(Convention::DEFAULT_LOCALE_VALUE));
        $useSession = array_try_get($localeSection, 'useSession', Convention::DEFAULT_LOCALE_USE_SESSION);
        $useCache = array_try_get($localeSection, 'useCache', Convention::DEFAULT_LOCALE_USE_CACHE);
        $defaultLocale = array_try_get($localeSection, 'defaultLocale', Convention::DEFAULT_LOCALE_VALUE);

        // try get lcid from request
        $lcid = $this->request()->get($localeParameter);
        
        if (empty($lcid) && $useSession && isset($_SESSION[Convention::SESSION_CURRENT_LOCALE]))
        {
            $lcid = $_SESSION[Convention::SESSION_CURRENT_LOCALE];
        }

        if (!empty($lcid))
        {
            // check if it is supported
            if (!in_array($lcid, $supportedLocales))
            {
                $this->log()->notice("Request Error! Requested locale[{$lcid}] not supported!");
                $lcid = null;
            }
        }

        if (empty($lcid))
        {
            // try get lcid from header
            $languages = $this->request()->getAcceptLanguages();
            if (!empty($languages) && !in_array($defaultLocale,$languages))
            {
                $supportedLanguages = array_intersect($languages, $supportedLocales);

                if (!empty($supportedLanguages))
                {
                    $lcid = array_shift($supportedLanguages);
                }
                else
                {
                    $lcid = $defaultLocale;
                    $this->log()->notice('Request Error! HTTP_ACCEPT_LANGUAGE[' . implode(' ', $languages) . '] not supported!');
                }
            }
            else
            {
                $lcid = $defaultLocale;
            }
        }

        setlocale(LC_ALL, $lcid);
        $this->_currentLocale = $lcid;

        if ($useSession)
        {
            $_SESSION[Convention::SESSION_CURRENT_LOCALE] = $lcid;
        }

        if ($useCache)
        {
            /*
            $cache = $this->cache('locale');
            if (!isset($cache))
            {
                throw new \Bluefin\Exception\ConfigException("Locale cache not found in config while 'useCache' is specified.");
            }

            $key = Convention::CACHE_KEY_PREFIX_LOCALE . $this->_currentLocale;
            $localeCache = $cache->get($key);
            if (isset($localeCache))
            {
                $this->_localeText = unserialize($localeCache);
                //[+]DEBUG
                $this->log()->debug('Cached locale text loaded.');
                //[-]DEBUG
            }
            */
        }

        /*
        if (!isset($this->_localeText))
        {
            $this->_localeText = array();

            if (ENABLE_LOCALE_EXPORT)
            {
                $this->_localePath = array();
            }

            $fullPath = BLUEFIN_BUILTIN . '/locale/' . $this->_currentLocale . '/*' . Convention::FILE_TYPE_LOCALE_FILE;

            foreach (glob($fullPath) as $filename)
            {
                $domain = basename($filename, Convention::FILE_TYPE_LOCALE_FILE);
                $localeCache = Yaml::load($filename);
                $this->_localeText[$domain] = isset($localeCache) ? $localeCache : array();

                if (ENABLE_LOCALE_EXPORT)
                {
                    $this->_localePath[$domain] = $filename;
                }

                //[+]DEBUG
                $this->log()->debug("Loading locale text from file system for domain: {$domain}");
                //[-]DEBUG
            }

            $fullPath = APP_LOCALE . '/' . $this->_currentLocale . '/*' . Convention::FILE_TYPE_LOCALE_FILE;

            foreach (glob($fullPath) as $filename)
            {
                $domain = basename($filename, Convention::FILE_TYPE_LOCALE_FILE);
                $localeCache = Yaml::load($filename);
                $this->_localeText[$domain] = isset($localeCache) ? $localeCache : array();

                if (ENABLE_LOCALE_EXPORT)
                {
                    $this->_localePath[$domain] = $filename;
                }
                
                //[+]DEBUG
                $this->log()->debug("Loading locale text from file system for domain: {$domain}");
                //[-]DEBUG
            }

            if ($useCache)
            {
                $cache->set($key, serialize($this->_localeText));
            }
        }
        */
    }

    private function _exportLocale($domain)
    {
        if (is_array($this->_localePath) && array_key_exists($domain, $this->_localePath))
        {
            $exportFile = $this->_localePath[$domain];
        }
        else
        {
            $exportPath = APP_LOCALE . '/' . $this->_currentLocale;
            $exportFile = $exportPath . '/' . $domain . Convention::FILE_TYPE_LOCALE_FILE;

            ensure_dir_exist($exportPath);
        }

        $domainText = $this->_localeText[$domain];
        file_put_contents($exportFile, Yaml::dump($domainText), LOCK_EX);
    }
}