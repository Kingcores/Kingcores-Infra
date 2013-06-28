<?php
//Don't edit this file which is generated by Bluefin Lance.
namespace WBT\Model\Weibotui;

use Bluefin\Convention;
use Bluefin\Data\ValidatorInterface;

class PersonalNameOrder implements ValidatorInterface
{
    const LAST_FIRST = 'last_first';
    const FIRST_LAST = 'first_last';

    private static $_data;

    public static function getDictionary()
    {
        if (!isset(self::$_data))
        {
            self::$_data = array(
                self::LAST_FIRST => _META_('weibotui.personal_name_order.last_first'),
                self::FIRST_LAST => _META_('weibotui.personal_name_order.first_last'),
            );
        }

        return self::$_data;
    }

    public static function getValues()
    {
        $data = self::getDictionary();
        return array_keys($data);
    }

    public static function getDisplayName($value)
    {
        $data = self::getDictionary();
        return $data[$value];
    }

    public static function getDefaultValue()
    {
        return self::LAST_FIRST;
    }

    public function validate($value)
    {
        $data = self::getDictionary();
        return array_key_exists($value, $data);
    }
}