<?php

namespace Bluefin\Modifier;

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
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_LT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_LTE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_GT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_GTE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_DEFAULT);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_DIGITS);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_PRECISION);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_FORBID_MANUAL_INITIAL);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_FORBID_MANUAL_UPDATE);
        $this->_trie->add(\Bluefin\Lance\Convention::MODIFIER_TYPE_POST_PROCESSOR);

        //\Bluefin\App::getInstance()->log()->debug(var_export($this->_trie, true));\Bluefin\App::getInstance()->log()->debug(var_export($this->_trie, true));
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
                $modifierPairs[$modifierToken] = trim(substr($modifier, strlen($modifierToken)));
            }
        }

        return $modifierPairs;
    }
}
