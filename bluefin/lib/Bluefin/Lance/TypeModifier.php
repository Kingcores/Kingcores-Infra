<?php

namespace Bluefin\Lance;

use Bluefin\Data\Type;
use Bluefin\Util\Trie;
 
class TypeModifier
{
    private static $_instance;

    /**
     * @static
     * @return TypeModifier
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance))
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private $_trie;

    private function __construct()
    {
        $this->_trie = new Trie();
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_COMMENT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_LT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_LTE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_GT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_GTE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_DEFAULT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_LENGTH);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_PRECISION);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_NO_INSERT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_NO_UPDATE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_POST_PROCESSOR);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_ON);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_ON_DELETE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_ON_UPDATE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_NO_INPUT);
    }

    public function parseModifiers(array $modifiers)
    {
        $modifierPairs = array();

        foreach ($modifiers as $modifier)
        {
            $modifierToken = $this->_trie->findLongestMatch($modifier);
            //\Bluefin\App::getInstance()->log()->debug("{$modifier} find: {$modifierToken}");

            if (isset($modifierToken))
            {
                $value = trim(substr($modifier, strlen($modifierToken)));
                $value = trim_quote($value);
                $modifierPairs[$modifierToken] = $value;
            }
        }

        return $modifierPairs;
    }
}
