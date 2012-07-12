<?php

namespace Bluefin\Data;
 
class Relation
{
    private $_tableMetadata;

    /**
     * @var string left-size table name 
     */
    private $_leftTable;
    /**
     * @var string field name of left table
     */
    private $_leftField;
    /**
     * @var string right-size table name
     */
    private $_rightTable;
    /**
     * @var string right-size table name
     */
    private $_rightField;

    private $_leftAlias;

    private $_rightAlias;

    private $_dotName;

    private $_prefix;

    public function __construct(Relations $relations, $relationToken, ModelMetadata $baseTableMetadata, $baseDotName, $prefix)
    {
        list($left, $right) = explode(':', $relationToken);
        list($this->_leftTable, $this->_leftField) = explode('.', $left);
        list($this->_rightTable, $this->_rightField) = explode('.', $right);

        \Bluefin\App::assert($this->_leftTable == $baseTableMetadata->getModelName());

        $this->_leftAlias = $relations->getTableAlias($baseDotName);

        /**
         * @var \Bluefin\Data\Model $modelClass
         */
        $modelClass = $relations->getTableMetadata()->getDatabase()->getModelClass($this->_rightTable);
        $this->_tableMetadata = $modelClass::s_metadata();

        $this->_dotName = isset($baseDotName) ? make_dot_name($baseDotName, $this->_leftField) : $this->_leftField;

        $this->_prefix = $prefix;

        $this->_rightAlias = array_get_auto_inc_name($relations->getAliases(), \Bluefin\Convention::getTableAliasNaming($this->_rightTable));
    }

    public function getTableMetadata()
    {
        return $this->_tableMetadata;
    }
    
    public function getLeftTableName()
    {
        return $this->_leftTable;
    }
    
    public function getLeftFieldName()
    {
        return $this->_leftField;
    }
    
    public function getRightTableName()
    {
        return $this->_rightTable;
    }
    
    public function getRightFieldName()
    {
        return $this->_rightField;
    }

    public function getDotName()
    {
        return $this->_dotName;
    }

    public function getColumnPrefix()
    {
        return $this->_prefix;
    }

    public function getLeftTableAlias()
    {
        return $this->_leftAlias;
    }

    public function getRightTableAlias()
    {
        return $this->_rightAlias;
    }
}
