<?php

namespace Common\Helper;

use WBT\Business\SmsBusiness;
use WBT\Business\MailBusiness;

class Alert
{
    public static function sendAlertSMS($message)
    {
        $mobileList = _C('config.custom.alert.sms');

        foreach ($mobileList as $mobile)
        {
            SmsBusiness::send($mobile, $message);
        }
    }

    public static function sendAlertEmail($subject, $message)
    {
        $email = _C('config.custom.alert.email');

        MailBusiness::sendMail($email, $subject, $message);
    }
}
