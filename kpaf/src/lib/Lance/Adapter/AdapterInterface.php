<?php

namespace Bluefin\Lance\Adapter;

use Bluefin\Lance\Entity;
use Bluefin\Lance\Field;
use Bluefin\Lance\SchemaSet;

interface AdapterInterface
{
    function isDefaultValueSupportedInDefinition($fieldType, $defaultValue);
    function getEntitySQLDefinition(Entity $entity);
    function getFieldSQLDefinition(Field $field);
    function getForeignConstraintSQLDefinition(Field $field);
    function translateValue($value, $type);
    function translateSQL(SchemaSet $schemaSet, $command, $target, array $columns, array $data);
}