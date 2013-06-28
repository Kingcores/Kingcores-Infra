<?php

namespace WBT\Data;

use WBT\Model\Weibotui\UserBusinessType;

class BusinessCode
{
    private static $__businessTypeIDTable;

    private static function _getTypeID($type)
    {
        if (!isset(self::$__businessTypeIDTable))
        {
            self::$__businessTypeIDTable = [
                UserBusinessType::WEIBO_ORDER => '80',
            ];
        }

        if (!array_key_exists($type, self::$__businessTypeIDTable))
        {
            throw new \Bluefin\Exception\InvalidOperationException("Unknown business type: {$type}!");
        }

        return self::$__businessTypeIDTable[$type];
    }

    private static $__businessBatchTokenTable;

    private static function _getBatchToken($type)
    {
        if (!isset(self::$__businessBatchTokenTable))
        {
            self::$__businessBatchTokenTable = [
                UserBusinessType::WEIBO_ORDER => 'WO',
            ];
        }

        if (!array_key_exists($type, self::$__businessBatchTokenTable))
        {
            throw new \Bluefin\Exception\InvalidOperationException("Unknown batch type: {$type}!");
        }

        return self::$__businessBatchTokenTable[$type];
    }

    public static function getSerialNo($type, $id)
    {
        $typeID = self::_getTypeID($type);

        return str_pad_crc($typeID . str_pad($id, 10, "0", STR_PAD_LEFT) . rand(0, 9), 1);
    }

    public static function getBatchID($type, $id)
    {
        $typeID = self::_getBatchToken($type);

        return $typeID . $id;
    }
}
