<?php

namespace Bluefin\Data;

use Bluefin\App;
use Bluefin\Data\Db\DbInterface;

class Select
{
    /**
     * @var \Bluefin\Data\Relations
     */
    private $_relations;

    private $_select;
    private $_join;
    private $_where;
    private $_groupBy;
    private $_orderBy;

    public function __construct($relations)
    {
        $this->_relations = $relations;
    }

    public function getRelations()
    {
        return $this->_relations;
    }

    public function getSelect()
    {
        return $this->_select;
    }

    public function setSelect($value)
    {
        $this->_select = $value;
        return $this;
    }

    public function getAlias()
    {
        return $this->_relations->hasAnyRelations() ?
            $this->_relations->getTableAlias() :
            null;
    }

    public function getFrom()
    {
        return $this->_relations->getTableMetadata()->getModelName();
    }

    public function getJoin()
    {
        return $this->_join;
    }

    public function setJoin($value)
    {
        $this->_join = $value;
        return $this;
    }

    public function getWhere()
    {
        return $this->_where;
    }

    public function setWhere($value)
    {
        $this->_where = $value;
        return $this;
    }

    public function getGroupBy()
    {
        return $this->_groupBy;
    }

    public function setGroupBy($value)
    {
        $this->_groupBy = $value;
        return $this;
    }

    public function getOrderBy()
    {
        return $this->_orderBy;
    }

    public function setOrderBy($value)
    {
        $this->_orderBy = $value;
        return $this;
    }
}
