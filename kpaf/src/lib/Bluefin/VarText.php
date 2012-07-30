<?php

namespace Bluefin;

use Bluefin\Util\Trie;

class VarText
{
    const VAR_TEXT_VAR_PATTERN = '/\{%(`?\w+(?:\.\w+)*(?:\|[^\|\}]+)*)\}/';

    private $_useBluefinContext;
    private $_context;

    /**
     * @var Trie
     */
    private $_handlersTrie;

    public function __construct(array $context = null, $useBluefinContext = false, array $handlers = null)
    {
        $this->_context = $context;
        $this->_useBluefinContext = $useBluefinContext;
        $this->_handlersTrie = new Trie();

        if (isset($handlers))
        {
            foreach ($handlers as $handler)
            {
                /**
                 * @var VarModifierHandler $handler
                 */

                $this->_handlersTrie->add($handler->getModifierToken(), $handler);
            }
        }
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
        return _CONTEXT($matches[1], null, $this->_useBluefinContext,
            $this->_context, !$this->_useBluefinContext,
            $this->_handlersTrie);
    }
}
