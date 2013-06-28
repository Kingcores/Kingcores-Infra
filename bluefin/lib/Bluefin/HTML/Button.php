<?php

namespace Bluefin\HTML;

class Button extends SimpleComponent
{
    const TYPE_DEFAULT = 'button';
    const TYPE_SUBMIT = 'submit';
    const TYPE_RESET = 'reset';
    const TYPE_TOGGLE = 'toggle';

    public $title;
    public $link;
    public $active;
    public $disabled;
    public $buttonType;

    public function __construct($title, $link, array $attributes = null)
    {
        parent::__construct($attributes);

        $this->buttonType = array_try_get($this->attributes, 'type', self::TYPE_DEFAULT, true);

        $this->title = $title;
        $this->link = $link;
        $this->active = false;

        $this->addFirstClass('btn');
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();

        if ($this->active)
        {
            $this->addClass('active');
        }

        if ($this->buttonType == self::TYPE_TOGGLE)
        {
            $this->attributes['type'] = self::TYPE_DEFAULT;
        }
        else
        {
            $this->attributes['type'] = $this->buttonType;
        }

        $isScript = false;
        if (isset($this->link) && mb_substr($this->link, 0, 11) == 'javascript:')
        {
            $this->link = mb_substr($this->link, 11);
            $isScript = true;
        }

        if ($isScript)
        {
            $this->link = str_pad_if($this->link, ';', false, true);
            $this->attributes['onclick'] = $this->link;
        }
        else if ($this->buttonType == self::TYPE_TOGGLE)
        {
            $this->attributes['data-toggle'] = $this->link;
        }
        else if (!empty($this->link))
        {
            $this->attributes['data-link'] = $this->link;
        }
    }

    protected function _renderContent()
    {
        $content = '<button' . $this->renderAttributes() . '>';
        $content .= $this->title;
        $content .= '</button>';

        return $content;
    }
}
