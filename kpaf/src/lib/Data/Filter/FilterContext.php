<?php

namespace Bluefin\Data\Filter;

use Bluefin\Data\Model;
 
class FilterContext
{
    private $_model;
    private $_data;

    public function __construct(Model $model, array $data = null)
    {
        $this->_model = $model;
        $this->_data = $data;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function setData(array $data)
    {
        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }
}
