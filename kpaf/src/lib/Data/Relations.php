<?php

namespace Bluefin\Data;

use Bluefin\App;

class Relations
{
    const LINK_PATTERN = '/^\w+.\w+:\w+.\w+$/';

    /**
     * @var \Bluefin\Data\ModelMetadata
     */
    private $_tableMetadata;

    /**
     * link => Relation
     * @var array
     */
    private $_relations;

    private $_relationsByDotName;

    private $_aliases;

    public function __construct(ModelMetadata $tableMetadata)
    {
        $this->_tableMetadata = $tableMetadata;
        $this->_relations = array();
        $this->_relationsByDotName = array();
        $this->_aliases = array('' => \Bluefin\Convention::getTableAliasNaming($this->_tableMetadata->getModelName()));
    }

    public function getTableMetadata()
    {
        return $this->_tableMetadata;
    }

    public function getRelations()
    {
        return $this->_relations;
    }

    /**
     * @param $dotName
     * @return \Bluefin\Data\Relation
     */
    public function getRelationByDotName($dotName)
    {
        return array_try_get($this->_relationsByDotName, $dotName);
    }

    public function hasAnyRelations()
    {
        return !empty($this->_relations);
    }

    public function getTableAlias($tablePath = null)
    {
        return isset($tablePath) ? $this->_aliases[$tablePath] : $this->_aliases[''];
    }

    public function getAliases()
    {
        return array_values($this->_aliases);
    }

    public function getFieldOptions($tablePath, $fieldName)
    {
        $result = isset($tablePath) ?
            $this->getRelationByDotName($tablePath)->getTableMetadata()->getFilterOption($fieldName) :
            $this->_tableMetadata->getFilterOption($fieldName);


        if (is_null($result))
        {
            throw new \Bluefin\Exception\InvalidOperationException(
                "Unknown column: {$tablePath}.{$fieldName}"
            );
        }


        return $result;
    }

    public function splitRelationships($inputWithRelationships, array &$output, $isCondition = false, $dotName = null)
    {
        if (is_array($inputWithRelationships))
        {
            foreach ($inputWithRelationships as $key => $value)
            {
                $this->_processEntry($dotName, $output, $key, $value, $isCondition);
            }
        }
        else if (isset($inputWithRelationships))
        {
            $this->_processEntry($dotName, $output, $inputWithRelationships, null, $isCondition);
        }
        else if (!$isCondition)
        {
            $this->_processEntry($dotName, $output, '*', null, false);
        }
    }

    /**
     * @param $dotNamePrefix
     * @param array $output
     * @param $key
     * @param $value
     * @param $isCondition
     * @return mixed
     * @throws \Bluefin\Exception\InvalidOperationException
     */
    private function _processEntry($dotNamePrefix, array &$output, $key, $value, $isCondition)
    {
        $baseTableMetadata = isset($dotNamePrefix) ?
            $this->getRelationByDotName($dotNamePrefix)->getTableMetadata() :
            $this->_tableMetadata;

        //echo "key: {$key}, value:{$value}<br>";

        if (is_int($key))
        {
            if ($isCondition)
            {
                if (isset($dotNamePrefix) || !isset($value))
                {
                    throw new \Bluefin\Exception\InvalidOperationException(
                        "Invalid condition."
                    );
                }

                $output[] = $value;

                return;
            }

            $key = $value;
            $value = null;
        }

        if ($this->_isDbExpression($key))
        {
            if (isset($dotNamePrefix))
            {
                throw new \Bluefin\Exception\InvalidOperationException(
                    "Invalid expression."
                );
            }

            $output[$key] = $value;
        }
        else if ($this->_isLink($key))
        {
            // 1. xxx.yyy:www.zzz => array()
            // 2. xxx.yyy:www.zzz => prefix
            //echo "{$key} => {$value}<br>";
            if (isset($value) && !is_array($value) && $value != '*')
            {
                $relation = $this->_addRelation($dotNamePrefix, $baseTableMetadata, $key, $value);
                $this->splitRelationships('*', $output, $isCondition, $relation->getDotName());
            }
            else
            {
                $relation = $this->_addRelation($dotNamePrefix, $baseTableMetadata, $key);
                $this->splitRelationships($value, $output, $isCondition, $relation->getDotName());
            }
        }
        else if ($this->_isDotName($key))
        {
            // 'xxx.yyy', 'xxx.yyy.*'
            $parts = explode('.', $key, 2);

            $localField = $parts[0];
            $relationToken = $baseTableMetadata ->getRelation($localField);

            if (!isset($relationToken))
            {
                throw new \Bluefin\Exception\InvalidOperationException(
                    "Table [{$baseTableMetadata->getModelName()}] has no relation on field [{$localField}]."
                );
            }

            if (is_array($value) || $value == '*')
            {
                throw new \Bluefin\Exception\InvalidOperationException(
                    "Invalid usage."
                );
            }

            //echo "{$parts[1]}<br>";

            if (isset($value) && $parts[1] == '*')
            {
                $relation = $this->_addRelation($dotNamePrefix, $baseTableMetadata, $relationToken, $value);
                $this->_processEntry($relation->getDotName(), $output, '*', null, $isCondition);
            }
            else
            {
                $relation = $this->_addRelation($dotNamePrefix, $baseTableMetadata, $relationToken);
                $this->_processEntry($relation->getDotName(), $output, $parts[1], $value, $isCondition);
            }
        }
        else
        {
            if ($key == '*')
            {
                if ($isCondition)
                {
                    throw new \Bluefin\Exception\InvalidOperationException(
                       '"*" cannot be used in a query condition.'
                    );
                }

                if (isset($value))
                {
                    throw new \Bluefin\Exception\InvalidOperationException(
                       'Alias cannot be set to field using "*".'
                    );
                }

                if (isset($dotNamePrefix))
                {
                    $relation = $this->getRelationByDotName($dotNamePrefix);

                    //echo $relation->getColumnPrefix() ."<br>";

                    foreach ($baseTableMetadata->getFieldNames() as $fieldName)
                    {
                        $dotName = make_dot_name($dotNamePrefix, $fieldName);
                        $output[$dotName] = ($relation->getColumnPrefix() == '') ? null : ($relation->getColumnPrefix() . '_' . $fieldName);
                    }
                }
                else
                {
                    foreach ($baseTableMetadata->getFieldNames() as $fieldName)
                    {
                        $output[$fieldName] = null;
                    }
                }
            }
            else
            {
                $dotName = isset($dotNamePrefix) ? make_dot_name($dotNamePrefix, $key) : $key;
                $output[$dotName] = $value;
            }
        }
    }

    private function _isLink($token)
    {
        return 1 === preg_match(self::LINK_PATTERN, $token);
    }

    private function _isDotName($token)
    {
        return false !== mb_strpos($token, '.');
    }

    private function _isDbExpression($token)
    {
        return false !== mb_strpos($token, '(');
    }

    /**
     * @param $baseDotName
     * @param ModelMetadata $baseTableMetadata
     * @param $relationToken
     * @param string $prefix
     * @return Relation
     */
    public function _addRelation($baseDotName, ModelMetadata $baseTableMetadata, $relationToken, $prefix = '')
    {
        if (array_key_exists($relationToken, $this->_relations)) return $this->_relations[$relationToken];

        $relation = new Relation($this, $relationToken, $baseTableMetadata, $baseDotName, $prefix);

        $this->_aliases[$relation->getDotName()] = $relation->getRightTableAlias();

        $this->_relations[$relationToken] = $relation;

        $this->_relationsByDotName[$relation->getDotName()] = $relation;

        return $relation;
    }
}
