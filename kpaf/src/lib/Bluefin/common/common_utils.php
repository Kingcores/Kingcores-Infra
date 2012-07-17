<?php

/**
 * 检查某哈希表是否拥有所有指定的键值。
 * @param $key_set 键值数组。
 * @param $collection 目标哈希表。
 * @return boolean
 */
function all_keys_exists(array $array, array $collection)
{
    foreach ($array as $e)
    {
        if (!array_key_exists($e, $collection)) return false;
    }
    
    return true;
}

/**
 * 检查是否有一个以上的键存在于指定的哈希表中。
 * @param array $array
 * @param array $collection
 * @return bool
 */
function any_keys_exists(array $array, array $collection)
{
    foreach ($array as $e)
    {
        if (array_key_exists($e, $collection)) return true;
    }

    return false;
}

function all_values_exists(array $array, array $collection)
{
    foreach ($array as $e)
    {
        if (!in_array($e, $collection)) return false;
    }

    return true;
}

/**
 * 将元素放入集合（集合没有重复的元素）。
 * @param $var 元素值。
 * @param $array 集合。 
 */
function array_push_unique(array &$array, $var)
{
    $num = func_num_args();
    if ($num == 2)
    {
        if (is_array($var))
        {
            foreach ($var as $v)
            {
                in_array($v, $array) || ($array[] = $v);
            }
        }
        else if (!in_array($var, $array))
        {
            $array[] = $var;
        }
    }
    else
    {
        for ($i = 1; $i < $num; ++$i)
        {
            $arg_var = func_get_arg($i);     
            in_array($arg_var, $array) || ($array[] = $arg_var);
        }
    }
}

function array_erase(array &$array, $var)
{
    $key = array_search($var, $array);
    if (false === $key) return;

    unset($array[$key]);
}

function array_get_auto_inc_name(array $array, $name)
{
    $append = 2;
    $value = $name;
    while (in_array($value, $array))
    {
        $value = $name . $append;
        $append++;
    }

    return $value;
}

/**
 * 给字符串数组中每个字符串拼接一个指定值。
 * @param $array 字符串数组。 
 * @param $var 需要拼接的值。
 * @param $right 左边还是右边。
 */
function array_str_pad(array $array, $var, $left = false, $right = true)
{
    $result = $array;
    $leftPad = $left ? $var : '';
    $rightPad = $right ? $var : '';
    foreach ($result as &$value)
    {
        $value = $leftPad . $value . $rightPad;
    }
    
    return $result;
}

function array_get_all(array $array, array $keys, $pop = false)
{
    $result = array();

    foreach ($keys as $key)
    {
        if (array_key_exists($key, $array))
        {
            $result[$key] = $array[$key];
            if ($pop) unset($array[$key]);
        }
    }

    return $result;
}

/**
 * 如果关联数组中存在该键值，则返回该键值对应的值，否则返回第三个参数（默认为null）。
 * @param $array
 * @param $key
 * @param null $default
 * @return mixed
 */
function array_try_get(&$array, $key, $default = null, $pop = false)
{
    if (!isset($key) || is_array($key))
    {
        debug_print_backtrace();
    }

    if (array_key_exists($key, $array))
    {
        $result = $array[$key];
        if ($pop) unset($array[$key]);

        return $result;
    }

    return $default;
}

/**
 * 从一个数组中，按照正则表达式查找匹配键，并返回该键对应的值。
 * @param array $array
 * @param $regex
 * @param bool $all
 * @return array|null 如果$all为true，则返回数组包含所有匹配的键值对，否则返回第一个匹配的值，如果没有匹配的，返回null
 */
function array_get_by_reg(array $array, $regex, $all = false)
{
    $result = array();
    foreach ($array as $key => $val)
    {
        if (preg_match($regex, $key, $matches))
        {
            if ($all)
            {
                $result[$key] = $val;
            }
            else
            {
                return array($key, $val, $matches);
            }
        }
    }

    return empty($result) ? null : $result;
}

/**
 * 从一个数组中，按照正则表达式查找匹配的值，并返回该值。
 * @return mixed 如果$all为true，则返回数组包含所有的值，否则返回第一个匹配的值，如果没有匹配的，返回null
 */
function array_search_by_reg(array &$array, $regex, $all = false)
{
    $result = array();
    foreach ($array as $val)
    {
        if (preg_match($regex, $val))
        {
            if ($all)
            {
                $result[] = $val;
            }
            else
            {
                return $val;
            }
        }
    }

    return empty($result) ? null : $result;
}

function array_search_by_filters(array &$array, array $filters)
{
    foreach ($array as $key => $val)
    {
        $found = true;

        foreach ($filters as $filter => $value)
        {
            if ($val[$filter] != $value)
            {
                $found = false;
                break;
            }
        }

        if ($found) return $key;
    }

    return false;
}

/**
 * 为字符串加上某个字符，如果不是以该字符开头或结尾
 * @param $str
 * @param $char
 * @param bool $right
 * @return string
 */
function str_pad_if($str, $sub_str, $left = false, $right = true)
{
    $l = strlen($sub_str);
    $left && $str != '' && substr($str, 0, $l) != $sub_str && ($str = $sub_str . $str);

    $right && $str != '' && substr($str, -$l) != $sub_str && ($str .= $sub_str);

    return $str;
}

/**
 * 获得两个CSV字符串的公共字串。
 * @param  $str1
 * @param  $str2
 * @return string
 */
function str_csv_intersect($str1, $str2)
{
    $str1Arr = explode(',', $str1);
    $str2Arr = explode(',', $str2);
    
    return implode(',', array_intersect($str1Arr, $str2Arr));
}

function str_is_quoted($str, $single = true, $double = true)
{
    if ($str == '') return false;

    $len = mb_strlen($str) - 1;

    if ($str[0] == "'" && $single) return mb_substr($str, $len, 1) == "'";
    if ($str[0] == '"' && $double) return mb_substr($str, $len, 1) == '"';

    return false;
}

function str_quote($str, $doubleQuote = false)
{
    $quote = $doubleQuote ? '"' : "'";
    $str = str_replace($quote, "\\{$quote}", $str);
    return "{$quote}{$str}{$quote}";
}

function trim_quote($str)
{
    return str_is_quoted($str) ? substr($str, 1, -1) : $str;
}

/**
 * 支持中文的JSON编码方法。
 * @param  $data
 * @return mixed
 */
function json_encode_cn($data)
{
    return preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", json_encode($data));
}

/**
 * 
 * @param $uri
 * @param $params array
 * @return string
 */
function build_uri($uri, array $queryParams = null, array $fragmentParams = null)
{
    if (empty($queryParams) && empty($fragmentParams)) return $uri;

    $parse_url = parse_url($uri);

    // Add our params to the parsed uri
    if ($queryParams)
    {
        if (isset($parse_url["query"]))
        {
            $parse_url["query"] .= "&" . http_build_query($queryParams);
        }
        else
        {
            $parse_url["query"] = http_build_query($queryParams);
        }
    }

    if ($fragmentParams)
    {
        if (isset($parse_url["fragment"]))
        {
            $parse_url["fragment"] .= "&" . http_build_query($fragmentParams);
        }
        else
        {
            $parse_url["fragment"] = http_build_query($fragmentParams);
        }
    }

    // Put humpty dumpty back together
    return
      ((isset($parse_url["scheme"])) ? $parse_url["scheme"] . "://" : "")
      . ((isset($parse_url["user"])) ? $parse_url["user"] . ((isset($parse_url["pass"])) ? ":" . $parse_url["pass"] : "") . "@" : "")
      . ((isset($parse_url["host"])) ? $parse_url["host"] : "")
      . ((isset($parse_url["port"])) ? ":" . $parse_url["port"] : "")
      . ((isset($parse_url["path"])) ? $parse_url["path"] : "")
      . ((isset($parse_url["query"])) ? "?" . $parse_url["query"] : "")
      . ((isset($parse_url["fragment"])) ? "#" . $parse_url["fragment"] : "");
}

function uuid_gen()
{
    if (function_exists('uuid_create'))
    {
        return uuid_create();
    }
    else if (function_exists('com_create_guid'))
    {
        return trim(com_create_guid(), "{}");
    }
    else
    {
        die("No uuid generation function.");
    }
}

function fake_uuid()
{
    $chars = md5(uniqid(mt_rand(), true));
    $uuid = substr($chars,0,8) . '-';
    $uuid .= substr($chars,8,4) . '-';
    $uuid .= substr($chars,12,4) . '-';
    $uuid .= substr($chars,16,4) . '-';
    $uuid .= substr($chars,20,12);
    return $uuid;
}

function exec_shell_command($cmd)
{
    $output = array();

    exec("LANG=\"en_US.UTF8\" {$cmd}", $output, $return_var);

    if ($return_var != 0)
    {
        $outputMsg = implode("\n", $output);
        \Bluefin\App::getInstance()->log()->err("Shell command failed. Cmd: {$cmd}, Code: {$return_var}, Output: {$outputMsg}");
        return false;
    }

    return true;
}

/**
 * 根据配置返回实际的大小
 * @param  $string
 * @return int
 * @author Junzhao Sun
 */
function parse_size($string)
{
    preg_match('/^(?<size>-1|\d+)(?<unit>\D*)$/', $string, $matches);
    if($matches["size"] == -1) return -1;
    $size = $matches["size"];

    switch (strtolower($matches["unit"]))
    {
        case '':
        case 'b':
        case 'byte':
            return $size;
            break;
        case 'k':
        case 'kb':
            return $size * 1024;
            break;
        case 'm':
        case 'mb':
            return $size * 1024 * 1024;
            break;
        case 'g':
        case 'gb':
            return $size * 1024 * 1024 * 1024;
            break;
        default:
            return $string;
            break;
    }
}