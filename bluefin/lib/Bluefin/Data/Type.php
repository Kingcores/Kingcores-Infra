<?php

namespace Bluefin\Data;

use Bluefin\App;
use Bluefin\Convention;

class Type
{
    const TYPE_INT        = 'int';
    const TYPE_FLOAT      = 'float';
    const TYPE_BOOL       = 'bool';
    const TYPE_TEXT       = 'text';
    const TYPE_BINARY     = 'bin';
    const TYPE_DATE       = 'date';
    const TYPE_TIME       = 'time';
    const TYPE_DATE_TIME  = 'datetime';
    const TYPE_TIMESTAMP  = 'timestamp';

    const TYPE_IDNAME    = 'idname';
    const TYPE_DIGITS    = 'digits';
    const TYPE_EMAIL     = 'email';
    const TYPE_PHONE     = 'phone';
    const TYPE_MONEY     = 'money';
    const TYPE_PASSWORD  = 'password';
    const TYPE_URL       = 'url';
    const TYPE_PATH      = 'path';
    const TYPE_XML       = 'xml';
    const TYPE_JSON      = 'json';
    const TYPE_UUID      = 'uuid';
    const TYPE_IPV4      = 'ipv4';

    const FIELD_NAME = 'name'; // string

    const ATTR_TYPE = 'type'; // string
    const ATTR_MAX_EXCLUSIVE = 'emax'; // number
    const ATTR_MIN_EXCLUSIVE = 'emin'; // number
    const ATTR_MAX = 'max'; // number
    const ATTR_MIN = 'min'; // number
    const ATTR_INSERT_VALUE = 'default'; // value, function, db expression
    const ATTR_REQUIRED = 'required'; // required
    const ATTR_LENGTH = 'length'; // number
    const ATTR_FLOAT_PRECISION = 'precision'; // number
    const ATTR_PATTERN = 'pattern'; // regex
    const ATTR_READONLY_ON_INSERTING = 'roc'; // boolean
    const ATTR_READONLY_ON_UPDATING = 'rou'; // boolean
    const ATTR_DB_AUTO_INSERT = 'db_insert';
    const ATTR_PRE_COMMIT_FILTER = 'filter';
    const ATTR_ENUM = 'enum';
    const ATTR_STATE = 'state';
    const ATTR_NO_INPUT = 'no_input';

    const PATTERN_IDNAME = '/^[a-z_][a-z0-9_]*$/i';
    const PATTERN_PASSWORD = '/^\S+$/';
    const PATTERN_EMAIL = '/^[a-z0-9]([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i';
    const PATTERN_PHONE = '/^\+?[0-9]+[0-9\-\s\*\#]*[0-9\*\#]$/';
    const PATTERN_URL = '/^((http|https|ftp):\/\/(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|"|\'|:|\<|$|\.\s)$/ie';
    const PATTERN_PATH = '/^((\/)?\S*)+$/';
    const PATTERN_DIGITS = '/^\d+$/';
    const PATTERN_UUID = '/^[0-9a-fA-F]{8}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{12}$/';
    const PATTERN_IPV4 = '/^(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])$/';

    const TYPE_MONEY_DEFAULT_PRECISION = 2;

    const FORMAT_DATE = 'Y-m-d';
    const FORMAT_TIME = 'H:i:s';
    const FORMAT_DATETIME = 'Y-m-d H:i:s';

    private static $_builtinTypeTable;

    public static function getBuiltinTypes()
    {
        if (!isset(self::$_builtinTypeTable))
        {
            self::$_builtinTypeTable = array(
                self::TYPE_INT,
                self::TYPE_FLOAT,
                self::TYPE_BOOL,
                self::TYPE_TEXT,
                self::TYPE_BINARY,
                self::TYPE_DATE,
                self::TYPE_TIME,
                self::TYPE_DATE_TIME,
                self::TYPE_TIMESTAMP,
            
                self::TYPE_IDNAME,
                self::TYPE_DIGITS,
                self::TYPE_EMAIL,
                self::TYPE_PHONE,
                self::TYPE_MONEY,
                self::TYPE_PASSWORD,
                self::TYPE_URL,
                self::TYPE_PATH,
                self::TYPE_XML,
                self::TYPE_JSON,
                self::TYPE_UUID,
                self::TYPE_IPV4,
            );
        }        

        return self::$_builtinTypeTable;
    }

    public static function validateValue($value, array $fieldOption)
    {
        $fieldName = $fieldOption[self::FIELD_NAME];
        $type = $fieldOption[self::ATTR_TYPE];

        switch ($type)
        {
            case self::TYPE_INT:
                self::validateInteger($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_FLOAT:
            case self::TYPE_MONEY:
                self::validateFloat($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_BOOL:
                break;

            case self::TYPE_BINARY:
                self::validateBinary($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_DATE:
            case self::TYPE_TIME:
            case self::TYPE_DATE_TIME:
            case self::TYPE_TIMESTAMP:
                break;

            case self::TYPE_TEXT:
            case self::TYPE_JSON:
            case self::TYPE_XML:
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_PASSWORD:
                $fieldOption[self::ATTR_PATTERN] = self::PATTERN_PASSWORD;
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_IDNAME:
                $fieldOption[self::ATTR_PATTERN] = self::PATTERN_IDNAME;
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_DIGITS:
                $fieldOption[self::ATTR_PATTERN] = self::PATTERN_DIGITS;
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_EMAIL:
                $fieldOption[self::ATTR_PATTERN] = self::PATTERN_EMAIL;
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_PHONE:
                $fieldOption[self::ATTR_PATTERN] = self::PATTERN_PHONE;
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_URL:
                $fieldOption[self::ATTR_PATTERN] = self::PATTERN_URL;
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_PATH:
                $fieldOption[self::ATTR_PATTERN] = self::PATTERN_PATH;
                self::validateText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_UUID:
                if (strlen($value) != 16)
                {
                    throw new \Bluefin\Exception\InvalidRequestException(
                        _T(
                            "Invalid UUID value.",
                            Convention::LOCALE_APP
                        )
                    );
                }
                break;

            case self::TYPE_IPV4:
                self::validateByPattern($fieldName, $value, self::PATTERN_IPV4);
                break;

            default:
                App::assert(false, "Unknown type [{$type}] for the field [{$fieldName}].");
                break;
        }
    }

    public static function convertValue(&$value, array $fieldOption)
    {
        if ($value instanceof DbExpr || $value instanceof InvalidData)
        {
            return $value;
        }

        $fieldName = $fieldOption[self::FIELD_NAME];
        $type = $fieldOption[self::ATTR_TYPE];

        switch ($type)
        {
            case self::TYPE_INT:
                self::convertInt($fieldName, $value);
                break;

            case self::TYPE_FLOAT:
            case self::TYPE_MONEY:
                self::convertFloat($fieldName, $value, array_try_get($fieldOption, self::ATTR_FLOAT_PRECISION));
                break;

            case self::TYPE_BOOL:
                self::convertBool($fieldName, $value);
                break;

            case self::TYPE_BINARY:
                break;

            case self::TYPE_DATE:
                self::convertDatetime($fieldName, $value, self::FORMAT_DATE);
                break;

            case self::TYPE_TIME:
                self::convertDatetime($fieldName, $value, self::FORMAT_TIME);
                break;

            case self::TYPE_DATE_TIME:
            case self::TYPE_TIMESTAMP:
                self::convertDatetime($fieldName, $value, self::FORMAT_DATETIME);
                break;

            case self::TYPE_TEXT:
            case self::TYPE_JSON:
            case self::TYPE_XML:
                break;

            case self::TYPE_PASSWORD:
                break;

            case self::TYPE_IDNAME:
                $value = mb_strtolower($value);
                break;

            case self::TYPE_DIGITS:
                break;

            case self::TYPE_EMAIL:
                break;

            case self::TYPE_PHONE:
                break;

            case self::TYPE_URL:
                break;

            case self::TYPE_PATH:
                break;

            case self::TYPE_UUID:
                self::convertUUID($fieldName, $value);
                break;

            case self::TYPE_IPV4:
                break;

            default:
                App::assert(false, "Unknown type \"{$type}\" for the field \"{$fieldName}\".");
                break;
        }

        return $value;
    }

    // pattern
    public static function validateByPattern($fieldName, $fieldValue, $pattern)
    {
        if (preg_match($pattern, $fieldValue, $matches))
        {
            App::Assert($matches[0] == $fieldValue);
            return;
        }

        throw new \Bluefin\Exception\InvalidRequestException(
            _APP_(
                'Invalid "%name%" pattern.',
                array('%name%' => $fieldName)
            )
        );
    }

    // min, max, default, pattern, patternName
    public static function validateText($fieldName, $fieldValue, array $fieldOption)
    {
        $len = mb_strlen($fieldValue);

        if (isset($fieldOption[self::ATTR_MIN]) && $len < $fieldOption[self::ATTR_MIN])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    '"%name%" should not be shorter than %min% characters.',
                    Convention::LOCALE_APP,
                    array('%name%' => $fieldName, '%min%' => $fieldOption[self::ATTR_MIN])
                )
            );
        }

        if (isset($fieldOption[self::ATTR_MAX]) && $len > $fieldOption[self::ATTR_MAX])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    '"%name%" should not be longer than %max% characters.',
                    Convention::LOCALE_APP,
                    array('%name%' => $fieldName, '%max%' => $fieldOption[self::ATTR_MAX])
                )
            );
        }

        if (array_key_exists(self::ATTR_PATTERN, $fieldOption))
        {
            self::validateByPattern($fieldName, $fieldValue, $fieldOption[self::ATTR_PATTERN]);
        }
    }

    public static function validateBinary($fieldName, $fieldValue, array $fieldOption)
    {
       $len = strlen($fieldValue);

       if (isset($fieldOption[self::ATTR_MIN]) && $len < $fieldOption[self::ATTR_MIN])
       {
           throw new \Bluefin\Exception\InvalidRequestException(
               _T(
                   '"%name%" should not be shorter than %min% bytes.',
                   Convention::LOCALE_APP,
                   array('%name%' => $fieldName, '%min%' => $fieldOption[self::ATTR_MIN])
               )
           );
       }

       if (isset($fieldOption[self::ATTR_MAX]) && $len > $fieldOption[self::ATTR_MAX])
       {
           throw new \Bluefin\Exception\InvalidRequestException(
               _T(
                   '"%name%" should not be longer than %max% bytes.',
                   Convention::LOCALE_APP,
                   array('%name%' => $fieldName, '%max%' => $fieldOption[self::ATTR_MAX])
               )
           );
       }
    }

    // 转换为 int 后和 options 中的 min，max 比较
    public static function validateInteger($fieldName, $fieldValue, array $fieldOption)
    {
        if (!is_int_val($fieldValue))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    'The given "%name%" value is not a valid integer.',
                    Convention::LOCALE_APP,
                    array('%name%' => $fieldName)
                )
            );
}

        if (isset($fieldOption[self::ATTR_MAX]) && $fieldValue > $fieldOption[self::ATTR_MAX])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    '"%name%" should not be greater than %max%.',
                    Convention::LOCALE_APP,
                    array('%name%' => $fieldName, '%max%' => $fieldOption[self::ATTR_MAX])
                )
            );
        }

        if (isset($fieldOption[self::ATTR_MIN]) && $fieldValue < $fieldOption[self::ATTR_MIN])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    '"%name%" should not be less than %min%.',
                    Convention::LOCALE_APP,
                    array('%name%' => $fieldName, '%min%' => $fieldOption[self::ATTR_MIN])
                )
            );
        }
    }

    public static function validateFloat($fieldName, $fieldValue, array $fieldOption)
    {
        $max = array_try_get($fieldOption, self::ATTR_MAX);
        if (!isset($max))
        {
            $max = array_try_get($fieldOption, self::ATTR_MAX_EXCLUSIVE);
            $inclusive = false;
        }
        else
        {
            $inclusive = true;
        }

        if (isset($max) && ($fieldValue > $max || ($inclusive && $fieldValue == $max)))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    '"%name%" should not be greater than %max%.',
                    Convention::LOCALE_APP,
                    array('%name%' => $fieldName, '%max%' => $max)
                )
            );
        }

        $min = array_try_get($fieldOption, self::ATTR_MIN);
        if (!isset($min))
        {
            $min = array_try_get($fieldOption, self::ATTR_MIN_EXCLUSIVE);
            $inclusive = false;
        }
        else
        {
            $inclusive = true;
        }

        if (isset($min) && ($fieldValue < $min || ($inclusive && $fieldValue == $min)))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    '"%name%" should not be less than %min%.',
                    Convention::LOCALE_APP,
                    array('%name%' => $fieldName, '%min%' => $min)
                )
            );
        }
    }

    public static function convertInt($fieldName, &$value)
    {
        $value = intval($value);
    }

    public static function convertFloat($fieldName, &$value, $precision = null)
    {
        $value = floatval($value);

        if (isset($precision))
        {
            $value = round($value, $precision);
        }
    }

    public static function convertDatetime($fieldName, &$value, $format)
    {
        if (is_int($value) || ctype_digit($value))
        {
            $value = date($format, $value);

            if (false === $value)
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _APP_(
                        '"%name%" is expected to be timestamp.',
                        array('%name%' => if_null_then($fieldName, _DICT_('parameter')))
                    )
                );
            }
        }
    }

    public static function convertBool($fieldName, &$value)
    {
        if (true === $value)
        {
            $value = 1;
        }
        else if (false === $value)
        {
            $value = 0;
        }
        else
        {
            $trimmed = mb_strtolower(trim($value));
            if ($trimmed == '1' || $trimmed == 'true')
            {
                $value = 1;
            }
            else if ($trimmed == '0' || $trimmed == 'false')
            {
                $value = 0;
            }
            else
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _T(
                        '"%name%" is expected to be a boolean value.',
                        Convention::LOCALE_APP,
                        array('%name%' => if_null_then($fieldName, _DICT_('parameter')))
                    )
                );
            }
        }
    }

    public static function convertUUID($fieldName, &$value)
    {
        if (strlen($value) != 16)
        {
            if (1 !== preg_match(self::PATTERN_UUID, $value, $matches))
            {
                throw new \Bluefin\Exception\InvalidRequestException(
                    _T(
                        '"%name%" is not a valid UUID.',
                        Convention::LOCALE_APP,
                        array('%name%' => if_null_then($fieldName, _DICT_('parameter')))
                    )
                );
            }

            $value = str_replace('-', '', $value);
            $value = pack('H*', $value);
        }
    }
}
