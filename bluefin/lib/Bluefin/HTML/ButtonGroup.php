<?php

namespace Bluefin\HTML;

class ButtonGroup extends Container
{
    const TYPE_REGULAR = 'regular';
    const TYPE_RADIO = 'radio';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_DROPDOWN = 'dropdown';

    public static function fromDictionary(array $dictionary, $activeKey, $baseLink, $withAllStates = false, $linkClass = null, $label = null, $type = self::TYPE_RADIO, array $attributes = null)
    {
        $group = new ButtonGroup(null, $type, $label, $attributes);

        if ($type == self::TYPE_DROPDOWN)
        {
            $list = LinkList::fromDictionary($dictionary, $activeKey, $baseLink, $withAllStates, null, $label);

            $toggleButton = new Button($list->activeLinkLabel . ' <span class="caret"></span>', 'dropdown', ['type' => Button::TYPE_TOGGLE, 'class' => $linkClass]);
            $toggleButton->addClass('dropdown-toggle');
            $group->addComponent($toggleButton);
            $group->addComponent($list);
        }
        else
        {
            if ($withAllStates)
            {
                $dictionary = array_merge([\Bluefin\Data\Database::KW_ALL_STATES => _VIEW_('all')], $dictionary);
            }

            foreach ($dictionary as $key => $value)
            {
                $link = new Button($value, $baseLink . $key, ['type' => Button::TYPE_DEFAULT, 'class' => $linkClass]);
                $link->active = $key == $activeKey;

                $group->addComponent($link);
            }
        }

        return $group;
    }

    public $listType;

    public function __construct(array $components = null, $listType = self::TYPE_RADIO, $label = null, array $attributes = null)
    {
        parent::__construct($components, $attributes);

        $this->listType = $listType;
        $this->label = $label;

        $this->addFirstClass('btn-group');
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();

        if ($this->listType == self::TYPE_RADIO || $this->listType == self::TYPE_CHECKBOX)
        {
            $this->attributes['data-toggle'] = $this->listType;
        }
    }

    protected function _renderContent()
    {
        $content = '<div' . $this->renderAttributes() . ">\n";
        foreach ($this->components as $component)
        {
            $content .= "  {$component}\n";
        }
        $content .= '</div>';

        return $content;
    }
}
