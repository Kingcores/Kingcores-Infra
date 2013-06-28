<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Data\Type;

use Exception;

class PHPCodingLogic
{
    /**
     * Used in FileRenderer to render return value of a getter method.
     * @param $type
     * @return string
     */
    public static function getPhpTypeName($type)
    {
        switch ($type)
        {
            case Type::TYPE_INT:
                return 'int';

            case Type::TYPE_FLOAT:
            case Type::TYPE_MONEY:
                return 'float';

            case Type::TYPE_BOOL:
                return 'bool';

            case Type::TYPE_TEXT:
            case Type::TYPE_JSON:
            case Type::TYPE_XML:
            case Type::TYPE_PASSWORD:
            case Type::TYPE_IDNAME:
            case Type::TYPE_DIGITS:
            case Type::TYPE_EMAIL:
            case Type::TYPE_PHONE:
            case Type::TYPE_URL:
            case Type::TYPE_PATH:
            case Type::TYPE_BINARY:
            case Type::TYPE_DATE:
            case Type::TYPE_TIME:
            case Type::TYPE_DATE_TIME:
            case Type::TYPE_TIMESTAMP:
            case Type::TYPE_UUID:
            case Type::TYPE_IPV4:
                return 'string';

                default:
                App::assert(false, "Unknown data type: {$type}.");
                return 'unknown';
        }
    }

    public static function hasAutoFill($type)
    {
        return in_array($type, [Type::TYPE_DIGITS, Type::TYPE_PASSWORD, Type::TYPE_UUID]);
    }

    public static function evaluateValue(Field $field, $value)
    {
        if (self::isVarText($value))
        {
            $text = mb_substr($value, 2, -1);
            return \Bluefin\VarText::parseVarText($text);
        }

        if (self::isAutoFill($value))
        {
            if (!self::hasAutoFill($field->getFieldType()))
            {
                throw new \Bluefin\Lance\Exception\GrammarException("Type \"{$field->getFieldType()}\" has no auto-fill feature.");
            }

            $valueClassName = '\Bluefin\Data\Functor\Auto' . usw_to_pascal($field->getFieldType());

            /**
             * @var \Bluefin\Data\Functor\ProviderInterface $autoFiller
             */
            $autoFiller = new $valueClassName;
            return $autoFiller->apply($field->getFilters());
        }

        if (self::isTriggerFill($value))
        {
            return new \Bluefin\Data\InvalidData();
        }

        if (self::isPhpDefaultValue($value))
        {
            return mb_substr($value, 6, -1);
        }

        if (self::isDbExpression($value))
        {
            if (self::isDbExpressionVT($value))
            {
                $function = mb_substr($value, 8, -1);
                return new \Bluefin\Data\DbExpr($function, true);
            }

            $function = mb_substr($value, 6);
            if (str_is_quoted($function))
            {
                $function = mb_substr($function, 1, -1);
            }
            return new \Bluefin\Data\DbExpr($function);
        }

        return $value;
    }
    
    public static function translateValue(Field $field, $value)
    {
        if ($value instanceof PHPCodingLogic) return $value;

        if (self::isVarText($value))
        {
            $text = mb_substr($value, 1);
            $value = new PHPCodingLogic("new \\Bluefin\\Data\\Functor\\VarTextProvider({$text})");
        }
        else if (self::isAutoFill($value))
        {
            if (!self::hasAutoFill($field->getFieldType()))
            {
                throw new \Bluefin\Lance\Exception\GrammarException("Type \"{$field->getFieldType()}\" has no auto-fill feature.");
            }

            $valueClassName = usw_to_pascal($field->getFieldType());
            $value = new PHPCodingLogic("new \\Bluefin\\Data\\Functor\\Auto{$valueClassName}()");
        }
        else if (self::isTriggerFill($value))
        {
            $value = new PHPCodingLogic("new \\Bluefin\\Data\\InvalidData()");
        }
        else if (self::isDbExpression($value))
        {
            if (self::isDbExpressionVT($value))
            {
                $function = mb_substr($value, 7);
                $value = new PHPCodingLogic("new DbExpr({$function}, true)");
            }
            else
            {
                $function = mb_substr($value, 6);
                if (!str_is_quoted($function))
                {
                    $function = str_quote($function, true);
                }
                $value = new PHPCodingLogic("new DbExpr({$function})");
            }
        }
        else if (self::isPhpDefaultValue($value))
        {
            $value = mb_substr($value, 5);
            $value = new PHPCodingLogic($value);
        }

        return $value;
    }

    public static function isVarText($value)
    {
        return ($value[0] === Convention::KEYWORD_VAR_TEXT_PREFIX && str_is_quoted(mb_substr($value, 1)));
    }

    public static function isAutoFill($value)
    {
        return $value === Convention::KEYWORD_AUTO_VALUE;
    }

    public static function isTriggerFill($value)
    {
        return $value === Convention::KEYWORD_TRIGGER_VALUE;
    }

    public static function isDbExpression($value)
    {
        return (mb_substr($value, 0, 6) === Convention::KEYWORD_DB_EXPR_PREFIX);
    }

    public static function isDbExpressionVT($value)
    {
        return ($value[6] === Convention::KEYWORD_VAR_TEXT_PREFIX && str_is_quoted(mb_substr($value, 7)));
    }

    public static function isPhpDefaultValue($value)
    {
        return (mb_substr($value, 0, 5) === Convention::KEYWORD_PHP_DEFAULT_VALUE && str_is_quoted(mb_substr($value, 5)));
    }

    private $_code;

    public function __construct($code)
    {
        $this->_code = $code;
    }

    public function __toString()
    {
        return $this->_code;
    }
}
