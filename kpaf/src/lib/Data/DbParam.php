<?php

namespace Bluefin\Data;

use PDO;

class DbParam
{
    public $name;
    public $value;
    public $metaType;
    public $dbType;

    public function __construct($column, $value, array $fieldOption)
    {
        $type = $fieldOption[Type::FILTER_TYPE];

        $this->name = $column;
        $this->value = $value;
        $this->metaType = $type;
        
        switch ($type)
        {
            case Type::TYPE_INT:
            case Type::TYPE_BOOL:
                $this->dbType = PDO::PARAM_INT;
                break;

            case Type::TYPE_FLOAT:
            case Type::TYPE_MONEY:
            case Type::TYPE_DATE:
            case Type::TYPE_TIME:
            case Type::TYPE_DATE_TIME:
            case Type::TYPE_TIMESTAMP:
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
                $this->dbType = PDO::PARAM_STR;
                break;

            case Type::TYPE_BINARY:
            case Type::TYPE_UUID:
                $this->dbType = PDO::PARAM_LOB;
                break;

            default:
                \Bluefin\App::assert(false, "Unknown type [{$type}].");
                break;
        }
    }
}
