<?php

namespace Bluefin\Lance;

use Bluefin\Data\Type;

use Exception;

class PHPCodingLogic
{
    public static function translateValue(Field $field, $value, $evaluation = false)
    {
        if (!($value instanceof PHPCodingLogic))
        {
            if (self::isKeyword($value))
            {
                if ($value == Convention::KEYWORD_AUTO_VALUE)
                {
                    $valueClassName = usw_to_pascal($field->getFieldType());
                    $value = new PHPCodingLogic("new \\Bluefin\\Data\\Functor\\Auto{$valueClassName}()");
                }
                else
                {
                    throw new \Bluefin\Lance\Exception\GrammarException("Unsupported keyword: " . $value);
                }
            }
            else if (self::isFunctor($value))
            {
                $value = new PHPCodingLogic(self::parseFunctor(substr($value, strlen(Convention::KEYWORD_FUNCTOR_PREFIX))));
            }
            else if (self::isDbFunction($value))
            {
                if ($evaluation)
                {
                    throw new \Bluefin\Lance\Exception\GrammarException("DB function cannot be evaluated in PHP.");
                }

                $function = substr($value, strlen(Convention::KEYWORD_DB_FUNCTION_PREFIX));
                $value = new PHPCodingLogic("new \\Zend_Db_Expr('{$function}')");
            }
            else
            {
                return $value;
            }
        }

        if ($evaluation)
        {
            /**
             * @var PHPCodingLogic $value
             */
            $tmp = $value->__toString();
            $value = eval("return " . $tmp . ";");

            if ($value instanceof \Bluefin\Data\Functor\SupplierInterface)
            {
                /**
                 * @var \Bluefin\Data\Functor\SupplierInterface $value
                 */
                $value = $value->supply($field->getFilters());
            }
            else if ($value instanceof \Bluefin\Data\Functor\PostProcessorInterface)
            {
                throw new \Bluefin\Lance\Exception\GrammarException("Post processor cannot be evaluated in current context.");
            }
        }

        return $value;
    }

    public static function isKeyword($value)
    {
        return substr($value, 0, strlen(Convention::KEYWORD_KEYWORD_PREFIX)) == Convention::KEYWORD_KEYWORD_PREFIX;
    }

    public static function isFunctor($value)
    {
        return substr($value, 0, strlen(Convention::KEYWORD_FUNCTOR_PREFIX)) == Convention::KEYWORD_FUNCTOR_PREFIX;
    }

    public static function isDbFunction($value)
    {
        return substr($value, 0, strlen(Convention::KEYWORD_DB_FUNCTION_PREFIX)) == Convention::KEYWORD_DB_FUNCTION_PREFIX;
    }

    public static function parseFunctor($functorString)
    {
        $parts = explode('(', substr($functorString, 0, -1));
        $functorName = "new \\Bluefin\\Data\\Functor\\" . usw_to_pascal(array_shift($parts)) . "(";

        if ($parts[0] != '')
        {
            $parts = explode(',', $parts[0]);

            foreach ($parts as &$part)
            {
                $part = trim($part);
                if (!is_numeric($part) && !str_is_quoted($part))
                {
                    $part = str_quote($part);
                }
            }
            $functorName .= implode(', ', $parts);
        }
        $functorName .= ')';

        return $functorName;
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
