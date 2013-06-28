<?php

namespace WBT\Data;

use WBT\Model\Weibotui\IncomeType;
use WBT\Model\Weibotui\PayoutType;

class TransactionCode
{
    private static $__transactionSerialCode;

    public static function getSerialNo($type, $id)
    {
        if (!isset(self::$__transactionSerialCode))
        {
            self::$__transactionSerialCode = [
                IncomeType::ADVERTISER_DEPOSIT => '10',
                IncomeType::CHANGWEIBO_DEPOSIT => '11',
                PayoutType::TUIKE_PAYROLL => '50'
            ];
        }

        if (!array_key_exists($type, self::$__transactionSerialCode))
        {
            throw new \Bluefin\Exception\InvalidOperationException("Unknown transaction type: {$type}!");
        }

        return str_pad_crc(self::$__transactionSerialCode[$type] . date('ymd', time()) . str_pad($id % 100000000, 8, "0", STR_PAD_LEFT) . rand(0, 9), 1);
    }
}
