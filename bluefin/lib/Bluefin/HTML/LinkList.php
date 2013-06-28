<?php

namespace Bluefin\HTML;

class LinkList extends Container
{
    public static function fromDictionary(array $dictionary, $activeKey, $baseLink, $withAllStates = false, $linkClass = null, $label = null, array $attributes = null)
    {
        $list = new LinkList(null, $label, $attributes);

        if ($withAllStates)
        {
            $dictionary = array_merge([\Bluefin\Data\Database::KW_ALL_STATES => _VIEW_('all')], $dictionary);
        }

        foreach ($dictionary as $key => $value)
        {
            $link = new Link($value, $baseLink . $key, isset($linkClass) ? ['class' => $linkClass] : null);
            $link->active = $key == $activeKey;

            $list->addComponent($link);
        }

        $list->activeLinkLabel = $dictionary[$activeKey];

        return $list;
    }

    public $isDropdown;
    public $activeLinkLabel;

    public function __construct(array $components = null, $isDropdown = false, $label = null, array $attributes = null)
    {
        parent::__construct($components, $attributes);

        $this->isDropdown = $isDropdown;
        $this->label = $label;

        if ($this->isDropdown)
        {
            $this->addFirstClass('dropdown-menu');
        }
        else
        {
            $this->addFirstClass('nav');
        }
    }
}
