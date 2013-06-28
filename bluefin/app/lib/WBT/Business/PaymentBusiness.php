<?php

namespace WBT\Business;

use WBT\Model\Weibotui\Income;

class PaymentBusiness
{
    public static function getIncomeRecord($type, $billId)
    {
        $income = new Income([Income::TYPE => $type, Income::BILL_ID => $billId]);

        $data = $income->data();
        unset($data[Income::VENDOR_NO]);

        return $data;
    }
}
