<?php

namespace Bluefin\HTML;

class ComboBox extends SimpleComponent
{
    public $collection;
    public $selected;

    public function __construct(array $collection, array $attributes = null)
    {
        parent::__construct($attributes);

        $this->collection = $collection;
        $this->selected = array_try_get($this->attributes, 'value', null, true);
    }

    protected function _renderContent()
    {
        $content = '<select' . $this->renderAttributes() . ">\n";
        $selected = false;
        foreach ($this->collection as $key => $value)
        {
            $content .= "<option value=\"{$key}\"";
            if (!$selected && (!isset($this->selected) || $key == $this->selected))
            {
                $selected = true;
                $content .= ' selected';
            }
            $content .= ">{$value}</option>\n";
        }
        $content .= '</select>';

        return $content;
    }
}
