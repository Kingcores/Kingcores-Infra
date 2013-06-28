<?php

namespace Bluefin\HTML;

class CustomComponent extends SimpleComponent
{
    protected $_html;

    public function __construct($html, array $attributes = null)
    {
        if (isset($attributes))
        {
            $attributes['type'] = 'custom';
        }
        else
        {
            $attributes = ['type' => 'custom'];
        }

        parent::__construct($attributes);

        $this->_html = $html;
    }

    protected function _renderContent()
    {
        return $this->_html;
    }
}
