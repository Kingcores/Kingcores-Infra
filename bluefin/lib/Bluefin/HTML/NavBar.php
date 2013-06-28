<?php

namespace Bluefin\HTML;

class NavBar extends Container
{
    public function __construct($label = null, array $components = null, array $attributes = null)
    {
        parent::__construct($components, $attributes);

        $this->label = $label;
        $this->addFirstClass('navbar');
    }

    public function addComponent($component)
    {
        /**
         * @var SimpleComponent $component
         */
        $component->addClass('pull-left');

        return parent::addComponent($component);
    }
}