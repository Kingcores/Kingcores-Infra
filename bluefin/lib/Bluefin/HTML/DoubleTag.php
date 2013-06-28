<?php

namespace Bluefin\HTML;

class DoubleTag extends SimpleComponent
{
    public $content;

    public function __construct($tag, array $attributes = null)
    {
        parent::__construct($attributes, $tag);

        $this->content = array_try_get($this->attributes, 'value', '', true);
    }

    protected function _renderContent()
    {
        return "<{$this->tag}" . $this->renderAttributes() . '>' . html_entity_decode($this->content) . "</{$this->tag}>";
    }
}
