<?php

use Bluefin\App;

function _CONTEXT($name, $default = null, $cacheable = false, $this = null)
{
    $modifiers = split_modifiers($name);
    $name = array_shift($modifiers);

    $app = App::getInstance();

    if ($app->inRegistry($name))
    {
        $result = $app->getRegistry($name);
    }
    else
    {
        $keys = explode('.', $name, 3);
        $numKeys = count($keys);

        if ($numKeys > 1)
        {
            $source = strtolower($keys[0]);
            $topKey = $keys[1];

            if ($numKeys > 2)
            {
                $rest = $keys[2];
            }
        }
        else if (isset($this))
        {
            $source = 'this';
            $topKey = $keys[0];
        }
        else
        {
            throw new \Bluefin\Exception\InvalidOperationException("Unknown context name: " + $name);
        }

        switch ($source)
        {
            case 'this':
                if (isset($this))
                {
                    $result = array_try_get($this, $topKey);
                }
                else
                {
                    throw new \Bluefin\Exception\InvalidOperationException("Retrieving data from a NULL object is not allowed!");
                }
                break;
            case 'get':
                $result = $app->request()->getQueryParam($topKey);
                break;
            case 'route':
                $result = $app->request()->getRouteParam($topKey);
                break;
            case 'post':
                $result = $app->request()->getPostParam($topKey);
                break;
            case 'put':
                $result = $app->request()->getPutParam($topKey);
                break;
            case 'header':
                $result = $app->request()->getHttpHeader($topKey);
                break;
            case 'cookie':
                $result = $app->request()->getCookieParam($topKey);
                break;
            case 'auth':
                $result = $app->auth($topKey)->getAuthData();
                break;
            case 'request':
                $result = $this->_request->get($topKey);
                break;
            default:
                throw new \Bluefin\Exception\ServerErrorException("Unsupported context source: {$source}");
                break;
        }

        if (isset($rest) && $rest != '')
        {
            $result = _C($rest, $default, $result);
        }

        if (isset($result) && $cacheable)
        {
            $app->setRegistry($name, $result);
        }
    }

    if (isset($modifiers))
    {
        $result = apply_value_modifiers($result, $modifiers);
    }

    return $result;
}

/**
 * 根据默认的语言代号以及提供的文本和词汇领域，返回翻译过的目标语言文本。
 * @param string $message
 * @param string $domain
 * @param array|null $param
 * @return string
 */
function _T($message, $domain, array $param = null)
{
    $translatedMessage = App::getInstance()->translate($message, $domain);

    return isset($param) ? strtr($translatedMessage, $param) : $translatedMessage;
}

/**
 * 翻译METADATA数据的快捷方式
 * @param $text
 * @return string
 */
function _META_($text, array $param = null)
{
    return _T($text, \Bluefin\Convention::LOCALE_METADATA_DOMAIN, $param);
}

/**
 * 翻译BUILTIN内置的文本的快捷方式
 * @param $text
 * @return string
 */
function _BUILTIN_($text, array $param = null)
{
    return _T($text, \Bluefin\Convention::LOCALE_BLUEFIN_DOMAIN, $param);
}

/**
 * 根据链式点名称形式(dot-name-style)获取配置项，默认获取/app/etc/global.xxx.yml中的配置项。
 * @param string $key
 * @param mixed|null $default
 * @param array|null $array
 * @return mixed
 */
function _C($key, $default = null, array $array = null)
{
    is_null($array) && ($array = App::getInstance()->config());

    $parts = explode('.', $key);

    $key1 = $parts[0];
    if (!array_key_exists($key1, $array)) return $default;

    $config = $array[$key1];
    array_shift($parts);
    foreach ($parts as $part)
    {
        if (!isset($config[$part])) return $default;
        $config = $config[$part];
    }

    return $config;
}

/**
 * 组装URL。
 * @param null $routeName
 * @param null $params
 * @param array|null $queryParams
 * @param array|null $fragmentParams
 * @return string
 */
function _U($routeName = null, $params = null, array $queryParams = null, array $fragmentParams = null)
{
    /**
     * @var \Bluefin\Gateway $gateway
     */
    $gateway = App::getInstance()->getRegistry('gateway');

    if (!isset($gateway))
    {
        return App::getInstance()->request()->getLandingUrl();
    }

    return $gateway->url($routeName, $params, $queryParams, $fragmentParams);
}

/**
 * 组装route路径。
 * @param null $routeName
 * @param null $params
 * @param array|null $queryParams
 * @param array|null $fragmentParams
 * @return string
 */
function _R($routeName = null, $params = null, array $queryParams = null, array $fragmentParams = null)
{
    /**
     * @var \Bluefin\Gateway $gateway
     */
    $gateway = App::getInstance()->getRegistry('gateway');
    return $gateway->relUrl($routeName, $params, $queryParams, $fragmentParams);
}

/**
 * 读取yml文件。
 * @param $filename
 * @param null $path
 * @return array|mixed*
 */
function _Y($filename, $path = null)
{
    $result = App::loadYmlFileEx(build_path(APP_ETC . '/app', $filename));
    if (isset($path))
    {
        $result = _C($path, null, $result);
    }

    return $result;
}

/**
 * 执行SQL文件。
 * @param $dbName
 * @param $file
 * @return int
 * @throws Bluefin\Exception\FileNotFoundException
 */
function _SQL($dbName, $file)
{
    if (!file_exists($file))
    {
        throw new \Bluefin\Exception\FileNotFoundException($file);
    }

    //[+]DEBUG
    App::getInstance()->log()->debug("Running {$file} against {$dbName} ...");
    //[-]DEBUG

    $db = App::getInstance()->db($dbName);

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $lineNum => $line)
    {
        $line = trim($line);
        if (substr($line, 0, 2) == '--')
        {
            $lines[$lineNum] = '';
        }
        else if (strlen($line) > 0 && substr($line, -1) == ';')
        {
            $lines[$lineNum] = $line . '--CR';
        }
        else
        {
            $lines[$lineNum] = $line;
        }
    }

    $content = implode('', $lines);
    $batch = explode(";--CR", $content);
    $affected = 0;

    foreach ($batch as $sql)
    {
        if ($sql != '')
        {
            $affected += $db->getConnection()->exec($sql);
        }
    }

    return $affected;
}

/**
 * 执行SQL查询字符串。
 * @param $dbName
 * @param $sql
 * @return mixed
 */
function _QUERY($dbName, $sql)
{
    return App::getInstance()->db($dbName)->getConnection()->exec($sql);
}

/**
 * Dump数据供供使用。
 * @param $var
 * @param string $name
 */
function _DEBUG($var, $name = 'var')
{
    App::getInstance()->log()->debug("DUMP {$name}: " . var_export($var, true));
}