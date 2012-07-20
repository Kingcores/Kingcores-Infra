<?php

use Bluefin\Convention;
use Bluefin\Util\Trie;

function bluefin_autoload($class)
{
    if (class_exists($class, false) || interface_exists($class, false))
    {
        return true;
    }

    $file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
    
    if (DIRECTORY_SEPARATOR != '\\')
    {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
    }

    if (false === stream_resolve_include_path($file))
    {
        throw new \Bluefin\Exception\FileNotFoundException($file);
    }

    include_once $file;

    if (!class_exists($class, false) && !interface_exists($class, false))
    {
        throw new \Bluefin\Exception\BluefinException("File \"$file\" does not exist or class \"$class\" was not found in the file");
    }

    return true;
}

/**
 * Change underline-separated words into pascal-naming form.
 * @param  $underline_separated_words 
 * @return string
 */
function usw_to_pascal($underline_separated_words)
{
    $phrases = explode('_', $underline_separated_words);
    foreach ($phrases as &$phrase)
    {
        $phrase = strtolower($phrase);
        $phrase = ucwords(Convention::getPascalNaming($phrase, $phrase));
    }

    return implode('', $phrases);
}

function pascal_to_usw($pascal)
{
    $len = strlen($pascal);
    $start = 0;
    $words = array();

    for ($i = 1; $i < $len; ++$i)
    {
        if ($pascal[$i] == strtoupper($pascal[$i]))
        {
            $word = substr($pascal, $start, $i);
            $word[0] = strtoupper($word[0]);
            $words[] = $word;
            $start = $i;
        }
    }

    return implode('_', $words);
}

function usw_to_words($underline_separated_words)
{
    $phrases = explode('_', $underline_separated_words);
    foreach ($phrases as &$phrase)
    {
        $phrase = strtolower($phrase);
        $phrase = ucwords(Convention::getPascalNaming($phrase, $phrase));
    }

    return implode(' ', $phrases);
}

function usw_to_const($underline_separated_words)
{
    return strtoupper(strtr($underline_separated_words, array('-' => '_', ' ' => '_')));
}

/**
 * Change underline-separated words into pascal-naming form.
 * @param  $underline_separated_words
 * @return string
 */
function usw_to_camel($underline_separated_words)
{
    $phrases = explode('_', $underline_separated_words);
    $first = true;
    foreach ($phrases as &$phrase)
    {
        $phrase = strtolower($phrase);
        if ($first)
        {
            $first = false;
        }
        else
        {
            $phrase = ucwords(Convention::getPascalNaming($phrase, $phrase));
        }
    }

    return implode('', $phrases);
}

function combine_usw($prefix, $name)
{
    $a1 = explode('_', $prefix);
    $a2 = explode('_', $name);

    $l1 = count($a1);
    $l2 = count($a2);

    $offset = $l1 - $l2;
    if ($offset < 0) $offset = 0;

    $i1 = $offset;
    $i2 = 0;
    while ($i1 < $l1)
    {
        $e1 = $a1[$i1];
        $e2 = $a2[$i2];
        if ($e1 == $e2)
        {
            $i1++;
            $i2++;
        }
        else
        {
            $offset++;
            $i1 = $offset;
            $i2 = 0;
        }
    }

    if ($offset < $l1)
    {
        array_splice($a1, $offset);
    }

    return implode('_', array_merge($a1, $a2));
}

function is_null_then($value, $default = null)
{
    return is_null($value) ? $default : $value;
}

/**
 * Handling modifiers on a given value
 *
 * @param $value
 * @param array $modifiers modifiers to be applied to the value
 * @param \Bluefin\Util\Trie $handlersTrie handlers to handle each modifiers
 * @return mixed
 * @throws Bluefin\Exception\InvalidOperationException
 */
function apply_value_modifiers($value, array $modifiers, Trie $handlersTrie)
{

    foreach ($modifiers as $modifier)
    {
        $modifierHandler = $handlersTrie->findLongestMatch($modifier);

        if (isset($modifierHandler))
        {
            /**
             * @var \Bluefin\VarModifierHandler $modifierHandler
             */
            $modifierToken = $modifierHandler->getModifierToken();

            if ($modifierToken == $modifier)
            {
                $parameter = null;
            }
            else
            {
                $parameter = substr($modifier, strlen($modifierToken));
                $parameter = trim_quote($parameter);
            }

            if ($modifierHandler->hasParameter())
            {
                $value = call_user_func($modifierHandler->getHandler(), $value, $parameter);
            }
            else
            {
                $value = call_user_func($modifierHandler->getHandler(), $value);
            }
        }
        else
        {
            throw new \Bluefin\Exception\InvalidOperationException(
                "Handler for modifier '{$modifier}' is not given!"
            );
        }
    }

    return $value;
}

function is_dot_name($name)
{
    return false !== strpos($name, '.');
}

function make_dot_name()
{
    $args = func_get_args();
    return implode('.', $args);
}

function dot_name_normalize($name, $thisName)
{
    if ('this.' == substr($name, 0, 5))
    {
        return $thisName . '.' . substr($name, 5);
    }
    else if (false === strpos($name, '.'))
    {
        return $thisName . '.' . $name;
    }

    return $name;
}

/**
 * Split a string by '|' except '||', and trim each part
 * @param  $name
 * @return array
 */
function split_modifiers($name)
{
    $modifiers = explode(Convention::DELIMITER_MODIFIER, $name);

    $result = array();
    $last = null;
    $cat = false;

    foreach ($modifiers as $modifier)
    {
        if ($modifier == '')
        {
            if (isset($last))
            {
                $last .= Convention::DELIMITER_MODIFIER;
                $cat = true;
            }
            else
            {
                $last = '';
            }
        }
        else if ($cat)
        {
            $last .= $modifier;
            $cat = false;
            $result[] = trim($last);
            $last = null;
        }
        else
        {
            if (isset($last))
            {
                $result[] = trim($last);
            }
            $last = $modifier;
        }
    }

    if (isset($last))
    {
        $escaped = Convention::DELIMITER_MODIFIER . Convention::DELIMITER_MODIFIER;

        if (substr($last, -2) == $escaped)
        {
            $last = substr($last, 0, -1);
        }
        $result[] = trim($last);
    }

    return $result;
}

function merge_modifiers(array $parts)
{
    $translated = array();

    foreach ($parts as $part)
    {
        $pos = strpos($part, Convention::DELIMITER_MODIFIER);
        $len = strlen($part);
        while (false !== $pos)
        {
            $part = substr_replace($part, Convention::DELIMITER_MODIFIER, $pos, 0);
            $pos++;
            $len++;
            while ($pos < $len && $part[$pos] == Convention::DELIMITER_MODIFIER) $pos++;
            $pos = strpos($part, Convention::DELIMITER_MODIFIER, $pos);
        }

        $translated[] = $part;
    }

    return implode(Convention::DELIMITER_MODIFIER, $translated);
}

