<?php

namespace Bluefin\HTML;

class Link extends SimpleComponent
{
    public $title;
    public $link;
    public $active;

    public function __construct($title, $link, array $attributes = null)
    {
        parent::__construct($attributes);

        $this->title = $title;
        $this->link = $link;
        $this->active = false;
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();

        if ($this->active)
        {
            $this->addClass('active');
        }

        if (isset($this->dataContext))
        {
            $link = \Bluefin\VarText::parseVarText($this->link, $this->dataContext);
        }
        else
        {
            $link = $this->link;
        }

        $this->attributes['href'] = isset($link) ? $link : 'javascript:;';
    }

    protected function _renderContent()
    {
        $content = '<a' . $this->renderAttributes() . '>';
        $content .= $this->title;
        $content .= '</a>';

        return $content;
    }
}