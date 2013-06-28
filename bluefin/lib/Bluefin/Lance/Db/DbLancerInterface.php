<?php

namespace Bluefin\Lance\Db;

use Bluefin\Lance\Entity;
use Bluefin\Lance\Field;
use Bluefin\Lance\Schema;

interface DbLancerInterface
{
    function getEntitySQLDefinition(Entity $entity);
    function getFieldSQLDefinition(Field $field);
    function getForeignConstraintSQLDefinition(Field $field);
    function translateValue($value, $type);
    function translateSQL($command, $target, array $columns, array $data);
}