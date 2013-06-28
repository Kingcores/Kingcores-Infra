<?php

namespace Bluefin\HTML;

class MobileAuthCode extends SimpleComponent
{
    protected $_send;

    public function __construct(array $attributes = null)
    {
        parent::__construct($attributes, 'input');

        $this->_send = array_try_get($this->attributes, 'send', null, true);

        if (!array_key_exists('class', $this->attributes))
        {
            $this->attributes['class'] = 'input-small';
        }

        $this->attributes['type'] = 'text';
    }

    protected function _renderContent()
    {
        $input = parent::_renderContent();

        $id = null;

        if (isset($this->attributes['id']))
        {
            $id = $this->attributes['id'] . 'Button';
        }

        $button = new Button(_APP_('Send Verification Code'), $this->_send, ['id' => $id, 'class' => 'btn-success btn-mini', 'style' => 'margin-bottom: 10px;']);

        return $input . '&nbsp;' . (string)$button;
    }
}
