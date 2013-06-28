<?php

namespace Bluefin\HTML;

class CheckableGroup extends SimpleComponent
{
    const TYPE_RADIO = 'radio';
    const TYPE_CHECK = 'checkbox';

    public $collection;
    public $selected;

    public static function buildSingleCheckBox(array $attributes = null)
    {
        isset($attributes['value']) || ($attributes['value'] = '0');
        $label = array_try_get($attributes, 'label', null, true);
        return new self(self::TYPE_CHECK, ['1' => $label], $attributes);
    }

    protected $_buttonType;

    public function __construct($type = self::TYPE_RADIO, array $collection, array $attributes = null)
    {
        parent::__construct($attributes);

        $this->collection = $collection;
        $this->selected = array_try_get($this->attributes, 'value', null, true);;
        $this->_buttonType = $type;

        $this->addFirstClass($type == self::TYPE_RADIO ? 'radio' : 'checkbox');
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();
    }

    protected function _renderContent()
    {
        $content = '';
        $selected = false;

        $attributes = $this->attributes;
        $id = array_try_get($attributes, 'id', null, true);
        $name = array_try_get($attributes, 'name', '', true);
        $attr = $this->renderAttributes($attributes);
        $index = 0;

        foreach ($this->collection as $key => $value)
        {
            $content .= '<label' . $attr . '><input ';
            if (isset($id))
            {
                $content .= 'id="' . $id . ($index > 0 ? $index : '') . '" ';
            }
            $content .= "type=\"{$this->_buttonType}\" name=\"{$name}\" value=\"{$key}\"";
            if (!$selected)
            {
                if (!isset($this->selected))
                {
                    $selected = true;
                    $content .= ' checked';
                }
                else if (is_array($this->selected))
                {
                    if (in_array($key, $this->selected))
                    {
                        $content .= ' checked';
                    }
                }
                else if ($key == $this->selected)
                {
                    $selected = true;
                    $content .= ' checked';
                }
            }
            $content .= ">{$value}</label>\n";
            $index++;
        }

        return $content;
    }
}