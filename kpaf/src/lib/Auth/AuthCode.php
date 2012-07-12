<?php

namespace Bluefin\Auth;

class AuthCode
{
    const SUCCESS = 0;
    const FAILURE = -1;
    const FAILURE_IDENTITY_NOT_FOUND = -2;
    const FAILURE_CREDENTIAL_INVALID = -3;
    const FAILURE_IDENTITY_STATUS_INVALID = -4;
    const FAILURE_IDENTITY_ISLOGIN = -5;

    private static $_errorMessages;

    public static function getErrorMessage($code)
    {
        if (!isset(self::$_errorMessages))
        {
            self::$_errorMessages = array(
                self::FAILURE => 'Authentication failed',
                self::FAILURE_IDENTITY_NOT_FOUND => 'Identity not found',
                self::FAILURE_CREDENTIAL_INVALID => 'Invalid credential',
                self::FAILURE_IDENTITY_STATUS_INVALID => 'Identity status invalid',
                self::FAILURE_IDENTITY_ISLOGIN => 'Identity is logined'
            );
        }

        return _T(array_try_get(self::$_errorMessages, $code), \Bluefin\Convention::LOCALE_APP);
    }
}
