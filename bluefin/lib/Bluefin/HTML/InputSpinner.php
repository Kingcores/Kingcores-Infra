<?php

namespace Bluefin\HTML;

class InputSpinner extends SimpleComponent
{
    const TYPE_CURRENCY = 'currency';
    const TYPE_NUMBER = 'number';
    const TYPE_QUANTITY = 'quantity';

    public function __construct(array $attributes = null)
    {
        parent::__construct($attributes, 'input');

        $options = array_try_get($this->attributes, 'options', [], true);
        $this->addFirstClass('input-spinner');

        $type = array_try_get($this->attributes, 'type', self::TYPE_CURRENCY, true);

        if ($type == self::TYPE_CURRENCY)
        {
            isset($options['numberFormat']) || ($options['numberFormat'] = 'C');
        }
        else
        {
            isset($options['numberFormat']) || ($options['numberFormat'] = 'n');

            if ($type == self::TYPE_QUANTITY)
            {
                $options['step'] = 1;
            }
        }

        $this->_view->set('_inputSpinner', json_encode($options));
    }
}
