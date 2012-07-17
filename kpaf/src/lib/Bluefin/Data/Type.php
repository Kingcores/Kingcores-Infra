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

    const FIELD_NAME = 'n'; // string

    const FILTER_TYPE = 't'; // string
    const FILTER_MAX = 'mx'; // number
    const FILTER_MIN = 'mn'; // number
    const FILTER_MAX_INCLUSIVE = 'mxi'; // number
    const FILTER_MIN_INCLUSIVE = 'mni'; // number
    const FILTER_INSERT_VALUE = 'i'; // value, function, db expression
    const FILTER_UPDATE_VALUE = 'u'; // value, function, db expression
    const FILTER_REQUIRED = 'r'; // required
    const FILTER_INT_DIGITS = 'd'; // number
    const FILTER_FLOAT_PRECISION = 'pr'; // number
    const FILTER_PATTERN = 'pt'; // regex
    const FILTER_READONLY_ON_INSERTING = 'roc'; // boolean
    const FILTER_READONLY_ON_UPDATING = 'rou'; // boolean
    const FILTER_DB_AUTO_INSERT = 'dbi';
    const FILTER_VALIDATOR = 'val';
    const FILTER_POST_PROCESS_FUNCTOR = 'ppf';

    const PATTERN_IDNAME = '/^[a-z_][a-z0-9_]*$/i';
    const PATTERN_PASSWORD = '/^\S+$/';
    const PATTERN_EMAIL = '/^[a-z0-9]([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i';
    const PATTERN_PHONE = '/^\+?[0-9]+[0-9\-\s\*\#]*[0-9\*\#]$/';
    const PATTERN_URL = '/^((http|https|ftp):\/\/(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|"|\'|:|\<|$|\.\s)$/ie';
    const PATTERN_PATH = '/^((\/)?\S*)+$/';
    const PATTERN_DIGITS = '/^\d+$/';
    const PATTERN_UUID = '/^[0-9a-fA-F]{8}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{12}$/';

    const TYPE_MONEY_DEFAULT_PRECISION = 2;

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
            );
        }        

        return self::$_builtinTypeTable;
    }

    public static function validateValue($value, array $fieldOption)
    {
        $fieldName = $fieldOption[self::FIELD_NAME];
        $type = $fieldOption[self::FILTER_TYPE];

        switch ($type)
        {
            case self::TYPE_INT:
                self::_filterInteger($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_FLOAT:
            case self::TYPE_MONEY:
                self::_filterFloat($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_BOOL:
                break;

            case self::TYPE_BINARY:
                self::_filterBinary($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_DATE:
            case self::TYPE_TIME:
            case self::TYPE_DATE_TIME:
            case self::TYPE_TIMESTAMP:
                break;

            case self::TYPE_TEXT:
            case self::TYPE_JSON:
            case self::TYPE_XML:
                self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_PASSWORD:
                $fieldOption[self::FILTER_PATTERN] = self::PATTERN_PASSWORD;
                $value = self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_IDNAME:
                $fieldOption[self::FILTER_PATTERN] = self::PATTERN_IDNAME;
                $value = self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_DIGITS:
                $fieldOption[self::FILTER_PATTERN] = self::PATTERN_DIGITS;
                self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_EMAIL:
                $fieldOption[self::FILTER_PATTERN] = self::PATTERN_EMAIL;
                self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_PHONE:
                $fieldOption[self::FILTER_PATTERN] = self::PATTERN_PHONE;
                self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_URL:
                $fieldOption[self::FILTER_PATTERN] = self::PATTERN_URL;
                self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_PATH:
                $fieldOption[self::FILTER_PATTERN] = self::PATTERN_PATH;
                self::_filterText($fieldName, $value, $fieldOption);
                break;

            case self::TYPE_UUID:
                if (strlen($value) != 16)
                {
                    throw new \Bluefin\Exception\InvalidParamException(
                        $fieldName,
                        self::TYPE_UUID
                    );
                }
                break;

            default:
                App::assert(false, "Unknown type [{$type}] for the field [{$fieldName}].");
                break;
        }
    }

    public static function convertValue(&$value, array $fieldOption)
    {
        $fieldName = $fieldOption[self::FIELD_NAME];
        $type = $fieldOption[self::FILTER_TYPE];

        switch ($type)
        {
            case self::TYPE_INT:
                self::_convertInt($fieldName, $value);
                break;

            case self::TYPE_FLOAT:
            case self::TYPE_MONEY:
                self::_convertFloat($fieldName, $value, array_try_get($fieldOption, self::FILTER_FLOAT_PRECISION));
                break;

            case self::TYPE_BOOL:
                self::_convertBool($fieldName, $value);
                break;

            case self::TYPE_BINARY:
                break;

            case self::TYPE_DATE:
            case self::TYPE_TIME:
            case self::TYPE_DATE_TIME:
            case self::TYPE_TIMESTAMP:
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
                self::_convertUUID($fieldName, $value);
                break;

            default:
                App::assert(false, "Unknown type [{$type}] for the field [{$fieldName}].");
                break;
        }

        return $value;
    }

    public static function formatValue(&$value, array $fieldOption)
    {
        $fieldName = _T($fieldOption[self::FIELD_NAME], Convention::LOCALE_METADATA_DOMAIN);
        $type = $fieldOption[self::FILTER_TYPE];

        switch ($type)
        {
            case self::TYPE_INT:

                break;

            case self::TYPE_FLOAT:
            case self::TYPE_MONEY:

                break;

            case self::TYPE_BOOL:
                break;

            case self::TYPE_BINARY:

                break;

            case self::TYPE_DATE:
            case self::TYPE_TIME:
            case self::TYPE_DATE_TIME:
            case self::TYPE_TIMESTAMP:
                break;

            case self::TYPE_TEXT:
            case self::TYPE_JSON:
            case self::TYPE_XML:

                break;

            case self::TYPE_PASSWORD:

                break;

            case self::TYPE_IDNAME:
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
                $value = bin2hex($value);
                echo $value;
                break;

            default:
                App::assert(false, "Unknown type [{$type}] for the field [{$fieldName}].");
                break;
        }
    }

    // pattern
    private static function _filterPattern($fieldName, $fieldValue, $pattern)
    {
        if (preg_match($pattern, $fieldValue, $matches))
        {
            App::Assert($matches[0] == $fieldValue);
            return;
        }

        throw new \Bluefin\Exception\InvalidRequestException(
            _T(
                'The value does not match the required pattern of field "%name%".',
                Convention::LOCALE_BLUEFIN_DOMAIN,
                array('%name%' => $fieldName)
            )
        );
    }

    // min, max, default, pattern, patternName
    private static function _filterText($fieldName, $fieldValue, array $fieldOption)
    {
        $len = mb_strlen($fieldValue);

        if (isset($fieldOption[self::FILTER_MIN]) && $len < $fieldOption[self::FILTER_MIN])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    'The value length of field "%name%" should not be shorter than "%min%".',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array('%name%' => $fieldName, '%min%' => $fieldOption[self::FILTER_MIN])
                )
            );
        }

        if (isset($fieldOption[self::FILTER_MAX]) && $len > $fieldOption[self::FILTER_MAX])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    'The value length of field "%name%" should not be longer than "%max%".',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array('%name%' => $fieldName, '%max%' => $fieldOption[self::FILTER_MAX])
                )
            );
        }

        if (array_key_exists(self::FILTER_PATTERN, $fieldOption))
        {
            self::_filterPattern($fieldName, $fieldValue, $fieldOption[self::FILTER_PATTERN]);
        }
    }

    private static function _filterBinary($fieldName, $fieldValue, array $fieldOption)
    {
       $len = strlen($fieldValue);

       if (isset($fieldOption[self::FILTER_MIN]) && $len < $fieldOption[self::FILTER_MIN])
       {
           throw new \Bluefin\Exception\InvalidRequestException(
               _T(
                   'The value length of field "%name%" should not be shorter than "%min%".',
                   Convention::LOCALE_BLUEFIN_DOMAIN,
                   array('%name%' => $fieldName, '%min%' => $fieldOption[self::FILTER_MIN])
               )
           );
       }

       if (isset($fieldOption[self::FILTER_MAX]) && $len > $fieldOption[self::FILTER_MAX])
       {
           throw new \Bluefin\Exception\InvalidRequestException(
               _T(
                   'The value length of field "%name%" should not be longer than "%max%".',
                   Convention::LOCALE_BLUEFIN_DOMAIN,
                   array('%name%' => $fieldName, '%max%' => $fieldOption[self::FILTER_MAX])
               )
           );
       }
    }

    // 转换为 int 后和 options 中的 min，max 比较
    private static function _filterInteger($fieldName, $fieldValue, array $fieldOption)
    {
        if (isset($fieldOption[self::FILTER_MAX]) && $fieldValue > $fieldOption[self::FILTER_MAX])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    'The value of field "%name%" should not be greater than "%max%".',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array('%name%' => $fieldName, '%max%' => $fieldOption[self::FILTER_MAX])
                )
            );
        }

        if (isset($fieldOption[self::FILTER_MIN]) && $fieldValue < $fieldOption[self::FILTER_MIN])
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    'The value of field "%name%" should not be less than "%min%".',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array('%name%' => $fieldName, '%min%' => $fieldOption[self::FILTER_MIN])
                )
            );
        }
    }

    private static function _filterFloat($fieldName, $fieldValue, array $fieldOption)
    {
        $max = array_try_get($fieldOption, self::FILTER_MAX_INCLUSIVE);
        if (!isset($max))
        {
            $max = array_try_get($fieldOption, self::FILTER_MAX);
            $inclusive = false;
        }
        else
        {
            $inclusive = true;
        }

        if (isset($max) && ($fieldValue > $max || ($inclusive && $fieldValue === $max)))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    'The value of field "%name%" should not be greater than "%max%".',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array('%name%' => $fieldName, '%max%' => $max)
                )
            );
        }

        $min = array_try_get($fieldOption, self::FILTER_MIN_INCLUSIVE);
        if (!isset($min))
        {
            $min = array_try_get($fieldOption, self::FILTER_MIN);
            $inclusive = false;
        }
        else
        {
            $inclusive = true;
        }

        if (isset($min) && ($fieldValue < $min || ($inclusive && $fieldValue === $min)))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _T(
                    'The value of field "%name%" should not be less than "%min%".',
                    Convention::LOCALE_BLUEFIN_DOMAIN,
                    array('%name%' => $fieldName, '%min%' => $min)
                )
            );
        }
    }

    private static function _convertInt($fieldName, &$value)
    {
        try
        {
            $value = (int)\Zend_Locale_Format::getInteger($value);
        }
        catch (\Exception $e)
        {
            throw new \Bluefin\Exception\InvalidParamException(
                $fieldName,
                self::TYPE_INT
            );
        }
    }

    private static function _convertFloat($fieldName, &$value, $precision = null)
    {
        try
        {
            $option = array();
            isset($precision) && ($option['precision'] = (int)$precision);

            $value = (float)\Zend_Locale_Format::getFloat($value, $option);
        }
        catch (\Exception $e)
        {
            throw new \Bluefin\Exception\InvalidParamException(
                $fieldName,
                self::TYPE_FLOAT
            );
        }
    }

    private static function _convertBool($fieldName, &$value)
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
                throw new \Bluefin\Exception\InvalidParamException(
                    $fieldName,
                    self::TYPE_BOOL
                );
            }
        }
    }

    private static function _convertUUID($fieldName, &$value)
    {
        if (strlen($value) != 16)
        {
            if (1 !== preg_match(self::PATTERN_UUID, $value, $matches))
            {
                throw new \Bluefin\Exception\InvalidParamException(
                    $fieldName,
                    self::TYPE_UUID
                );
            }

            $value = str_replace('-', '', $value);
            $value = pack('H*', $value);
        }
    }
}
