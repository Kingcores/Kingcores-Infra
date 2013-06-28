<?php

namespace Common\Data;

use Bluefin\Auth\AuthHelper;

class Event
{
    //--------------------------------------------------------------------------------
    //bit 0~3
    const LEVEL_FATAL     = 0xf;
    const LEVEL_ERROR     = 0xe;
    const LEVEL_WARNING   = 0x8;
    const LEVEL_INFO      = 0x1;
    const LEVEL_SUCCESS   = 0x0;

    //--------------------------------------------------------------------------------
    //bit 4~11
    const CAT_COMMON        = 0x00;
    const CAT_WBT_GENERAL   = 0x01;
    const CAT_WBT_BMS       = 0x02;
    const CAT_WBT_MMS       = 0x03;
    const CAT_WBT_SOCIAL    = 0x04;

    const CAT_EXTERNAL      = 0xe0;

    //--------------------------------------------------------------------------------
    //bit 4~19

    //CAT_COMMON
    const SRC_COMMON        = 0x0000;

    //CAT_EXTERNAL
    const SRC_SINA_WEIBO    = 0xe001;
    const SRC_QQ_WEIBO      = 0xe002;

    //CAT_WBT_GENERAL
    const SRC_AUTH          = 0x0101;
    const SRC_SNA           = 0x0102;
    const SRC_REG           = 0x0103;
    const SRC_PAYMENT       = 0x0104;

    //--------------------------------------------------------------------------------
    //lower 32 bits

    //SRC_COMMON
    const E_MISSING_ARGUMENT            = -1;

    //SRC_AUTH
    const S_LOGIN_SUCCESS               = AuthHelper::SUCCESS;
    const E_LOGIN_FAILURE               = AuthHelper::FAILURE;
    const E_LOGIN_IDENTITY_NOT_FOUND    = AuthHelper::FAILURE_IDENTITY_NOT_FOUND;
    const E_LOGIN_CREDENTIAL_INVALID    = AuthHelper::FAILURE_CREDENTIAL_INVALID;
    const E_LOGIN_VERIFY_CODE_INVALID   = AuthHelper::FAILURE_VERIFY_CODE_INVALID;

    const E_ACCOUNT_DISABLED            = -10;

    //SRC_REG
    const S_ACTIVATE_SUCCESS            = 0;
    const I_ACCOUNT_ALREADY_ACTIVATED   = 1;
    const E_ACCOUNT_NONACTIVATED        = -1;
    const E_INVALID_TOKEN               = -2;

    const E_PASSWORD_CONFIRM            = -10;

    //SRC_SNA
    const S_SNA_LOGIN_SUCCESS   = 0x0000;
    const S_SNA_BIND            = 0x0001;
    const I_SNA_ALREADY_BIND    = 0x0002;
    const E_SNA_BIND_OTHER      = 0x0003;
    const I_SNA_UNBIND          = 0x0004;
    const S_REGISTER_BY_SNA     = 0x0010;

    //SRC_PAYMENT
    const S_DEPOSIT_ALIPAY  = 1;
    const S_DEPOSIT_TENPAY  = 2;

    const E_ALIPAY_FAIL = -1;
    const E_ALIPAY_INVALID = -2;

    //CAT_WBT_SOCIAL + SRC_AUTH
    const S_SO_LOGIN_SUCCESS    = AuthHelper::SUCCESS;

    const S_ADD_WEIBO           = 0x00010100;
    const S_ALREADY_ADD_WEIBO   = 0x00010101;
    //const S_SNA_ALREADY_BOUND   = 0x0001

    const S_ADD_WEIBO_CAMPAIGN  = 0x00030010;

    public static function fatal($source, $code)
    {
        return self::make(self::LEVEL_FATAL, $source, $code);
    }

    public static function error($source, $code)
    {
        return self::make(self::LEVEL_ERROR, $source, $code);
    }

    public static function warning($source, $code)
    {
        return self::make(self::LEVEL_WARNING, $source, $code);
    }

    public static function info($source, $code)
    {
        return self::make(self::LEVEL_INFO, $source, $code);
    }

    public static function success($source, $code)
    {
        return self::make(self::LEVEL_SUCCESS, $source, $code);
    }

    public static function make($level, $source, $code)
    {
        return (($level & 0xf) << 48) | (($source & 0xffff) << 32) | ($code & 0xffffffff);
    }

    public static function getEventLowerCode($bigCode)
    {
        return ($bigCode & 0x00000000ffffffff);
    }

    public static function getLevelAlertClass($bigCode)
    {
        $level = ($bigCode >> 48) & 0xf;
        $levelNames = [
            self::LEVEL_SUCCESS => ' alert-success',
            self::LEVEL_INFO => ' alert-info',
            self::LEVEL_WARNING => '',
            self::LEVEL_ERROR => ' alert-error',
            self::LEVEL_FATAL => ' alert-danger'
        ];

        return $levelNames[$level];
    }

    public static function getMessage($bigCode, array $params = null)
    {
        $code = $bigCode & 0xffffffff;
        $level = ($bigCode >> 48) & 0xf;
        $cat = ($bigCode >> 40) & 0x0ff;
        $src = ($bigCode >> 32) & 0x000ff;

        if ($cat == self::CAT_EXTERNAL)
        {
            $srcDomains = [
                self::SRC_SINA_WEIBO => 'sina_weibo',
                self::SRC_QQ_WEIBO => 'qq_weibo'
            ];

            $domain = $srcDomains[$src];
        }
        else
        {
            if ($level >= self::LEVEL_WARNING)
            {
                $domain = 'error';
            }
            else
            {
                $domain = 'info';
            }

            if ($cat != self::CAT_COMMON)
            {
                $catSuffixes = [
                    self::CAT_WBT_GENERAL => '_wbt',
                    self::CAT_WBT_BMS => '_bms',
                    self::CAT_WBT_MMS => '_mms',
                    self::CAT_WBT_SOCIAL => '_so'
                ];

                $domain .= $catSuffixes[$cat];
                $code = dechex($code | ($src << 32));
            }
        }

        return _T($code, $domain, $params);
    }
}
