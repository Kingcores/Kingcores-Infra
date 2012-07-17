<?php

namespace Bluefin\Data\Functor;

class Copy implements PostProcessorInterface
{
    private $_sourceFieldName;

    public function __construct($sourceFieldName)
    {
        $this->_sourceFieldName = $sourceFieldName;
    }

    public function process($rawValue, array $dataSet)
    {
        return $dataSet[$this->_sourceFieldName];
    }
}
