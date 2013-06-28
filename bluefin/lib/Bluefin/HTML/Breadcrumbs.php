<?php

namespace Bluefin\HTML;

class Breadcrumbs extends SimpleComponent
{
    protected $_location;

    public function __construct(array $location, array $attributes = null)
    {
        parent::__construct($attributes);

        $this->_location = $location;
        $this->addFirstClass('breadcrumb');
    }

    protected function _renderContent()
    {
        $keys = array_keys($this->_location);
        $lastKey = array_pop($keys);

        $components = [];
        $components[] = '<ul' . $this->renderAttributes() . '>';

        foreach ($keys as $key)
        {
            $components[] = "  <li><a href=\"{$this->_location[$key]}\">{$key}</a> <span class=\"divider\">/</span></li>";
        }

        $components[] = "  <li class=\"active\">$lastKey</li>";
        $components[] = '</ul>';

        return implode("\n", $components);
    }
}
