<?php

namespace Bluefin;

use Bluefin\Util\Trie;

class VarText
{
    /**
     * @var VarText
     */
    private static  $__default;

    public static function getDefaultProcessor()
    {
        if (!isset(self::$__default))
        {
            $handlers = array(
                new VTMWrapper(Convention::MODIFIER_NAMING_PASCAL, 'usw_to_pascal'),
                new VTMWrapper(Convention::MODIFIER_NAMING_CAMEL, 'usw_to_camel'),
                new VTMWrapper(Convention::MODIFIER_NAMING_UPPER, 'mb_strtoupper'),
                new VTMWrapper(Convention::MODIFIER_NAMING_LOWER, 'mb_strtolower'),
                new VTMWrapper(Convention::MODIFIER_TRIM, 'trim'),
                new VTMWrapper(Convention::MODIFIER_DEFAULT_VALUE, 'if_null_then', true),
                new VTMWrapper(Convention::MODIFIER_DATE_FORMAT, 'bluefin_date', true),
                new VTMWrapper(Convention::MODIFIER_MD5_SALT, 'md5_salt', true),
                new VTMWrapper(Convention::MODIFIER_MD5, 'md5'),
                new VTMWrapper(Convention::MODIFIER_TRANSLATE, '_T', true),
                new VTMWrapper(Convention::MODIFIER_CONTEXT, function ($value, $parameter, $context) { return _C($parameter . '.' . $value, null, $context); }, true, true),
                new VTMWrapper(Convention::MODIFIER_PREPEND, function ($value, $parameter) { return $parameter . $value; }, true),
                new VTMWrapper(Convention::MODIFIER_CONCAT, function ($value, $parameter) { return $value . $parameter; }, true),
                new VTMWrapper(Convention::MODIFIER_JSON, function ($value) { return is_array($value) ? json_encode($value) : $value; }),
                new VTMWrapper(Convention::MODIFIER_YAML, function ($value) { return is_array($value) ? \Symfony\Component\Yaml\Yaml::dump($value, 0) : $value; }),
                new VTMWrapper(Convention::MODIFIER_META, function ($value, $parameter) { return _META_($parameter . '.' . $value); }, true)
            );

            self::$__default = new VarText(null, $handlers);
        }

        return self::$__default;
    }

    public static function parseVarText($varText, $context = null)
    {
        $processor = self::getDefaultProcessor();

        $processor->setContext($context);

        $result = $processor->parse($varText);

        $processor->setContext(null);

        return $result;
    }

    const VAR_TEXT_VAR_PATTERN = '/\{\{\s*([\"\']?\w+(?:\.\w+)*[\"\']?\s*(?:\|[^\|\}]+)*)\s*\}\}/';

    private $_context;

    /**
     * @var Trie
     */
    private $_handlersTrie;

    public function __construct(array $context = null, array $handlers = null)
    {
        $this->_context = $context;
        $this->_handlersTrie = new Trie();

        if (isset($handlers))
        {
            foreach ($handlers as $handler)
            {
                /**
                 * @var VarTextModifier $handler
                 */

                $this->_handlersTrie->add($handler->getModifierToken(), $handler);
            }
        }
    }

    public function addHandler(VarTextModifier $handler)
    {
        $this->_handlersTrie->add($handler->getModifierToken(), $handler);
    }

    public function getContext()
    {
        return $this->_context;
    }

    public function setContext($context)
    {
        $this->_context = $context;
    }

    public function parse($varText)
    {
        return preg_replace_callback(
            self::VAR_TEXT_VAR_PATTERN,
            array(&$this, '_parseCallback'),
            $varText
        );
    }

    private function _parseCallback($matches)
    {
        return _C($matches[1], null,
            $this->_context,
            $this->_handlersTrie);
    }
}
