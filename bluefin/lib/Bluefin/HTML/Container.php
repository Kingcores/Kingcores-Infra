<?php

namespace Bluefin\HTML;

class Container extends Component
{
    public $components;

    public function __construct(array $components = null, array $attributes = null)
    {
        parent::__construct($attributes);

        $this->components = [];
        if (isset($components)) { $this->addComponents($components); }
    }

    public function addComponent($component)
    {
        $this->components[] = $component;

        return $this;
    }

    public function addComponents(array $components)
    {
        foreach ($components as $component)
        {
            $this->addComponent($component);
        }

        return $this;
    }
}
