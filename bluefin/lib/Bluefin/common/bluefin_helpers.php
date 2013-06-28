<?php

use Bluefin\App;
use Bluefin\Util\Trie;

/**
 * 获取环境上下文。
 *
 * @param $name
 * @param null $defaultValue
 * @param null $thisContext
 * @param Bluefin\Util\Trie $handlersTrie
 * @return array|float|int|mixed|null|string
 * @throws Bluefin\Exception\InvalidOperationException
 * @throws Bluefin\Exception\ServerErrorException
 */
function _C($name, $defaultValue = null,
            $thisContext = null,
            Trie $handlersTrie = null)
{
    $modifiers = split_modifiers($name);
    $name = array_shift($modifiers);

    if (str_is_quoted($name))
    {
        $result = trim_quote($name);
    }
    else
    {
        $app = App::getInstance();

        $keys = explode('.', $name, 3);
        $numKeys = count($keys);

        if ($numKeys > 1)
        {
            $source = $keys[0];
            $topKey = $keys[1];

            if ($numKeys > 2)
            {
                $rest = $keys[2];
            }
        }
        else if (isset($thisContext))
        {
            $source = 'this';
            $topKey = $keys[0] == 'this' ? null : $keys[0];
        }
        else
        {
            throw new \Bluefin\Exception\InvalidOperationException("Unknown context name: " + $name);
        }

        switch ($source)
        {
            case 'this':
                if (isset($thisContext))
                {
                    if (is_null($topKey))
                    {
                        $result = $thisContext;
                    }
                    else
                    {
                        $result = array_try_get($thisContext, $topKey);
                    }
                }
                else
                {
                    throw new \Bluefin\Exception\InvalidOperationException("Failed to fetch value '{$name}', because 'this' object is NULL!");
                }
                break;
            case 'auth':
                $result = $app->auth($topKey)->getData();
                break;
            case 'gateway':
                $result = $app->gateway()->getContext($topKey);
                break;
            case 'app':
                $result = $app->getContext($topKey);
                break;
            case 'config':
                $result = $app->config($topKey);
                break;
            case 'session':
                $result = array_try_get($_SESSION, $topKey);
                break;
            case 'request':
                $result = $app->request()->get($topKey);
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
            default:
                throw new \Bluefin\Exception\ServerErrorException("Unsupported context source: {$source}!");
                break;
        }

        if (isset($rest) && $rest != '' && isset($result))
        {
            $result = _V($rest, $result, $defaultValue);
        }
    }

    if (!empty($modifiers))
    {
        if (!isset($handlersTrie))
        {
            throw new \Bluefin\Exception\InvalidOperationException(
                "Missing modifier handlers!"
            );
        }

        $result = apply_modifiers($result, $modifiers, $handlersTrie, $thisContext);
    }

    return $result;
}

/**
 * 用于检查变量是否已初始化。
 * @param $name
 * @param $value
 * @throws Bluefin\Exception\InvalidRequestException
 */
function _ARG_IS_SET($name, $value)
{
    if (!isset($value))
    {
        throw new \Bluefin\Exception\InvalidRequestException(
            _APP_('Missing required value "%name%"!', ['%name%' => $name])
        );
    }
}

/**
 * 用于检查数组中的字段是否有提供。
 * @param $key
 * @param array $array
 * @throws Bluefin\Exception\InvalidRequestException
 */
function _ARG_EXISTS($key, array $array)
{
    if (!array_key_exists($key, $array))
    {
        throw new \Bluefin\Exception\InvalidRequestException(
            _APP_('Missing required field "%key%"!', ['%key%' => $key])
        );
    }
}

/**
 * @param Bluefin\Data\Model $model
 * @throws Bluefin\Exception\DataException
 */
function _NON_EMPTY(\Bluefin\Data\Model $model)
{
    if ($model->isEmpty())
    {
        throw new \Bluefin\Exception\DataException(
            _APP_("The requested '%name%' record does not exist.", [
                '%name%' => $model->metadata()->getDisplayName()
            ])
        );
    }
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
 * 翻译APP文本的快捷方式
 * @param $text
 * @param $param
 * @return string
 */
function _APP_($text, array $param = null)
{
    return _T($text, \Bluefin\Convention::LOCALE_APP, $param);
}

/**
 * 翻译ERROR文本的快捷方式
 * @param $errorCode
 * @param $param
 * @return string
 */
function _EMSG_($errorCode, array $param = null)
{
    return _T($errorCode, \Bluefin\Convention::LOCALE_ERROR, $param);
}

/**
 * @param $text
 * @param array $param
 * @return string
 */
function _VIEW_($text, array $param = null)
{
    return _T($text, \Bluefin\Convention::LOCALE_VIEW, $param);
}

/**
 * @param $word
 * @return string
 */
function _DICT_($word)
{
    return _T($word, \Bluefin\Convention::LOCALE_DICT);
}

/**
 * 翻译METADATA数据的快捷方式
 * @param $text
 * @param $param
 * @return string
 */
function _META_($text, array $param = null)
{
    return _T($text, \Bluefin\Convention::LOCALE_METADATA, $param);
}

/**
 * 根据链式点名称形式(dot-name-style)获取配置项，默认获取/app/etc/global.xxx.yml中的配置项。
 * @param string $key
 * @param array $array
 * @param mixed|null $default
 * @return mixed
 */
function _V($key, array $array, $default = null)
{
    if (empty($array)) return null;

    $parts = explode('.', $key);

    $key1 = $parts[0];
    if (!isset($array[$key1])) return $default;

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
 * @param null $relativeUrl
 * @param array $queryParams
 * @param array $fragmentParams
  * @return string
 */
function _U($relativeUrl = null, array $queryParams = null, array $fragmentParams = null, $https = false)
{
    /**
     * @var \Bluefin\Gateway $gateway
     */
    $gateway = App::getInstance()->gateway();
    return $gateway->url($relativeUrl, $queryParams, $fragmentParams, $https);
}

/**
 * 组装相对路径。
 * @param null $relativeUrl
 * @param array $queryParams
 * @param array $fragmentParams
 * @return string
 */
function _P($relativeUrl = null, array $queryParams = null, array $fragmentParams = null)
{
    /**
     * @var \Bluefin\Gateway $gateway
     */
    $gateway = App::getInstance()->gateway();
    return $gateway->path($relativeUrl, $queryParams, $fragmentParams);
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
    $gateway = App::getInstance()->gateway();
    return $gateway->route($routeName, $params, $queryParams, $fragmentParams);
}

/**
 * 读取yml文件。
 * @param $filename
 * @param null $path YML文件中的节点路径。
 * @return array|mixed*
 */
function _Y($filename, $path = null)
{
    $result = App::loadYmlFileEx(build_path(APP_ETC . '/app', $filename));
    if (isset($path))
    {
        $result = _V($path, $result);
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
            $affected += $db->getAdapter()->query($sql);
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
    return App::getInstance()->db($dbName)->getAdapter()->query($sql);
}

/**
 * Dump数据供供使用。
 * @param $var
 * @param string $name
 */
function _DEBUG($var, $name = 'var')
{
    $info = get_log_file_line_fun();
    App::getInstance()->log()->debug($info."DUMP {$name}: " . var_export($var, true));
}

function _ARR_DUMP(array $array)
{
    echo '<pre>' . nl2br(\Symfony\Component\Yaml\Yaml::dump($array, 10)) . '</pre>';
}

function get_log_file_line_fun()
{
    $trace = debug_backtrace();
    $depth = 0;
    $fun = '';
    if (isset($trace[$depth + 2])) {
        $fun = $trace[$depth + 2]['function'];
    }
    $file = basename($trace[$depth + 1]['file']);
    $line = $trace[$depth + 1]['line'];
    return "[file:$file][line:$line][fun:$fun]";
}

function log_debug($msg, $arr = null)
{
    // 只有在开发环境才打印debug日志
    //if (get_cfg_var('bluefin.env') != 'dev') {
    //    return;
    //}

    $info = get_log_file_line_fun();
    $str_arr = '';
    if (!empty($arr)) {
        if (is_array($arr)) {
            $str_arr = var_export($arr, true);
        } else if(!is_object($arr)) {
            $str_arr = $arr;
        }
    }
    App::getInstance()->log()->debug($info . $msg . $str_arr);
}

function log_warning($msg, $arr = null)
{
    $info = get_log_file_line_fun();
    $str_arr = '';
    if (!empty($arr)) {

        if (is_array($arr)) {
            $str_arr = var_export($arr, true);
        } else {
            $str_arr = $arr;
        }
    }
    App::getInstance()->log()->warning($info . $msg . $str_arr);
}

function log_error($msg, $arr = null)
{
    $info = get_log_file_line_fun();
    $str_arr = '';
    if (!empty($arr)) {

        if (is_array($arr)) {
            $str_arr = var_export($arr, true);
        } else {
            $str_arr = $arr;
        }
    }
    App::getInstance()->log()->error($info . $msg . $str_arr);
}

function log_warn($msg, $arr = null)
{
    $info = get_log_file_line_fun();
    $str_arr = '';
    if (!empty($arr)) {

        if (is_array($arr)) {
            $str_arr = var_export($arr, true);
        } else {
            $str_arr = $arr;
        }
    }
    App::getInstance()->log()->error($info . $msg . $str_arr);
}

function log_fatal($msg, $arr = null)
{
    $info = get_log_file_line_fun();
    $str_arr = '';
    if (!empty($arr)) {

        if (is_array($arr)) {
            $str_arr = var_export($arr, true);
        } else {
            $str_arr = $arr;
        }
    }
    App::getInstance()->log()->fatal($info . $msg . $str_arr);
}

function log_info($msg, $arr = null)
{
    $str_arr = '';
    if (!empty($arr)) {

        if (is_array($arr)) {
            $str_arr = var_export($arr, true);
        } else {
            $str_arr = $arr;
        }
    }
    App::getInstance()->log()->info($msg . $str_arr);
}

