<?php

namespace Bluefin\Util;

class Trie
{
    private $_trie = array();
    private $_value;

    public function __construct($value = null)
    {
        $this->_value = $value;
    }

    public function addCollection(array $array)
    {
        foreach ($array as $key => $value)
        {
            if (is_int($key))
            {
                $this->add($value, $value);
            }
            else
            {
                $this->add($key, $value);
            }
        }
    }

    public function add($string, $value = null)
    {
        if (!isset($value))
        {
            $value = $string;
        }

        if ($string == '')
        {
            $this->_value = $value;
            return;
        }

        /**
         * @var Trie $trie
         */
        foreach ($this->_trie as $prefix => $trie)
        {
            $prefixLength = strlen($prefix);
            $head = substr($string, 0, $prefixLength);
            $headLength = strlen($head);

            $equals = true;
            $equalPrefix = "";
            for ($i = 0; $i < $prefixLength; ++$i)
            {
                //Split
                if ($i >= $headLength)
                {
                    $equalTrie = new Trie($value);
                    $this->_trie[$equalPrefix] = $equalTrie;
                    $equalTrie->_trie[substr($prefix, $i)] = $trie;
                    unset($this->_trie[$prefix]);
                    return;
                }
                else if ($prefix[$i] != $head[$i])
                {
                    if ($i > 0)
                    {
                        $equalTrie = new Trie();
                        $this->_trie[$equalPrefix] = $equalTrie;
                        $equalTrie->_trie[substr($prefix, $i)] = $trie;
                        $equalTrie->_trie[substr($string, $i)] = new Trie($value);
                        unset($this->_trie[$prefix]);
                        return;
                    }
                    $equals = false;
                    break;
                }

                $equalPrefix .= $head[$i];
            }

            if ($equals)
            {
                $trie->add(substr($string, $prefixLength), $value);
                return;
            }
        }

        $this->_trie[$string] = new Trie($value);
    }

    public function search($string)
    {
        if (empty($string))
        {
            return $this->_value;
        }

        /**
         * @var Trie $trie
         */
        foreach ($this->_trie as $prefix => $trie)
        {
            $prefixLength = strlen($prefix);
            $head = substr($string, 0, $prefixLength);

            if ($head == $prefix)
            {
                return $trie->search(substr($string, $prefixLength));
            }
        }

        return null;
    }

    public function findLongestMatch($string)
    {
        return $this->_findLongestMatch($string, $this->_value);
    }

    public function _findLongestMatch($string, $value)
    {
        if (empty($string))
        {
            return isset($this->_value) ? $this->_value : $value;
        }

        /**
         * @var Trie $trie
         */
        foreach ($this->_trie as $prefix => $trie)
        {
            $prefixLength = strlen($prefix);
            $head = substr($string, 0, $prefixLength);

            if ($head == $prefix)
            {
                //\Bluefin\App::getInstance()->log()->debug("------------>find: {$prefix}, value: {$this->_value}");

                return $trie->_findLongestMatch(substr($string, $prefixLength), isset($this->_value) ? $this->_value : $value);
            }
        }

        return isset($this->_value) ? $this->_value : $value;
    }
}
