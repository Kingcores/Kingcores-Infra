<?php

namespace Bluefin\Data;

use Bluefin\App;

class Relations
{
    const LINK_PATTERN = '/^\w+.\w+:\w+.\w+$/';

    const M2N_LOCAL_FIELD = 0;
    const M2N_RELATION_FIELD = 1;
    const M2N_IS_UNIQUE = 2;
    const M2N_OTH_RELATION_FIELD = 3;
    const M2N_REFERENCED_ENTITY = 4;
    const M2N_REFERENCED_FIELD = 5;

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

    private $_groupBy;

    public function __construct(ModelMetadata $tableMetadata)
    {
        $this->_tableMetadata = $tableMetadata;
        $this->_relations = array();
        $this->_relationsByDotName = array();
        $this->_aliases = array('' => \Bluefin\Convention::getTableAliasNaming($this->_tableMetadata->getModelName()));
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

    /**
     * @param string $tablePath
     * @return ModelMetadata
     */
    public function getTableMetadata($tablePath = '')
    {
        return $tablePath == '' ? $this->_tableMetadata : $this->getRelationByDotName($tablePath)->getTableMetadata();
    }

    public function getTableAlias($tablePath = '')
    {
        return $this->_aliases[$tablePath];
    }

    public function getAliases()
    {
        return array_values($this->_aliases);
    }

    public function getFieldOptions($tablePath, $fieldName)
    {
        return $this->getTableMetadata($tablePath)->getFilterOption($fieldName);
    }

    /**
     * 分解出结果集中的字段和关系。
     *
     * @param $inputWithRelationships
     * @param array $output
     * @param bool $isCondition
     * @param null $dotName
     */
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
     * 处理查询的一个结果项。
     *
     * @param $dotNamePrefix 该结果项的相对点路径的父级点路径，如：表1.表1的关联表2.表2的关联表3。
     * @param array $output 输入查询语句的最终表述，如：名称 => 别名。
     * @param $key 该结果项的相对点路径。
     * @param $value 该结果项的别名。
     * @param $isCondition 标志位，表示是否用于查询条件，而非用于结果集。
     * @return mixed
     * @throws \Bluefin\Exception\InvalidOperationException
     */
    private function _processEntry($dotNamePrefix, array &$output, $key, $value, $isCondition)
    {
        $baseTableMetadata = isset($dotNamePrefix) ?
            $this->getRelationByDotName($dotNamePrefix)->getTableMetadata() :
            $this->_tableMetadata;

        if (is_int($key))
        {
            if ($isCondition)
            {//WHERE条件
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

        if ($key instanceof DbExpr)
        {
            if (isset($dotNamePrefix))
            {
                throw new \Bluefin\Exception\InvalidOperationException(
                    "Invalid expression."
                );
            }

            $keyPrefix = $key->isVarText() ? \Bluefin\Convention::DB_EXPR_VT_TAG : \Bluefin\Convention::DB_EXPR_TAG;

            $output[$keyPrefix . $key->__toString()] = $value;
        }
        else if (is_dot_name($key))
        {
            // 'xxx.yyy', 'xxx.yyy.*'
            $parts = explode('.', $key, 2);

            $localField = $parts[0];
            $restPart = $parts[1];
            $relationToken = $baseTableMetadata ->getRelation($localField);

            if (!isset($relationToken))
            {
                throw new \Bluefin\Exception\InvalidOperationException(
                    "Table [{$baseTableMetadata->getModelName()}] has no relation on field [{$localField}]."
                );
            }

            if (isset($value))
            {
                if ($isCondition || is_dot_name($restPart))
                {
                    $relation = $this->_addRelation($dotNamePrefix, $baseTableMetadata, $relationToken);
                    $this->_processEntry($relation->getDotName(), $output, $restPart, $value, $isCondition);
                }
                else
                {
                    $relation = $this->_addRelation($dotNamePrefix, $baseTableMetadata, $relationToken, $value);
                    $this->_processEntry($relation->getDotName(), $output, $restPart, null, $isCondition);
                }
            }
            else
            {
                $relation = $this->_addRelation($dotNamePrefix, $baseTableMetadata, $relationToken);
                $this->_processEntry($relation->getDotName(), $output, $restPart, null, $isCondition);
            }
        }
        /*
        else if ($this->_isLink($key))
        {
            // 1. xxx.yyy:www.zzz => array()
            // 2. xxx.yyy:www.zzz => prefix
            //echo "{$key} => {$value}<br>";
            if (isset($value) && !is_array($value))
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
        */
        else
        {
            $uswPrefix = str_replace('.', '_', $dotNamePrefix);

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

                    $colPrefix = $relation->getColumnPrefix();
                    if (!isset($colPrefix))
                    {
                        $colPrefix = $uswPrefix . '_';
                    }
                    else if ($colPrefix == '')
                    {
                        $colPrefix = '';
                    }
                    else
                    {
                        $colPrefix .= '_';
                    }

                    foreach ($baseTableMetadata->getFieldNames() as $fieldName)
                    {
                        $dotName = make_dot_name($dotNamePrefix, $fieldName);
                        $output[$dotName] = $colPrefix . $fieldName;
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
                if (isset($dotNamePrefix))
                {
                    $relation = $this->getRelationByDotName($dotNamePrefix);

                    $colPrefix = $relation->getColumnPrefix();
                    if (!isset($colPrefix))
                    {
                        $colPrefix = $uswPrefix . '_';
                    }
                    else if ($colPrefix == '')
                    {
                        $colPrefix = '';
                    }
                    else
                    {
                        $colPrefix .= '_';
                    }

                    $dotName = make_dot_name($dotNamePrefix, $key);

                    $output[$dotName] = isset($value) ? $value : ($colPrefix . $key);

                }
                else
                {
                    $options = $this->getFieldOptions('', $key);
                    if (isset($options))
                    {
                        $output[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * @param $baseDotName
     * @param ModelMetadata $baseTableMetadata
     * @param $relationToken
     * @param string $prefix
     * @return Relation
     */
    public function _addRelation($baseDotName, ModelMetadata $baseTableMetadata, $relationToken, $prefix = null)
    {
        if (array_key_exists($relationToken, $this->_relations)) return $this->_relations[$relationToken];

        $relation = new Relation($this, $relationToken, $baseTableMetadata, $baseDotName, $prefix);

        $this->_aliases[$relation->getDotName()] = $relation->getRightTableAlias();

        $this->_relations[$relationToken] = $relation;

        $this->_relationsByDotName[$relation->getDotName()] = $relation;

        return $relation;
    }
}
