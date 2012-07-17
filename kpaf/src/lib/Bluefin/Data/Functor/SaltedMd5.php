<?php

namespace Bluefin\Data\Functor;

class SaltedMd5 implements PostProcessorInterface
{
    private $_saltFieldName;

    public function __construct($saltFieldName)
    {
        $this->_saltFieldName = $saltFieldName;
    }

    public function process($rawValue, array $dataSet)
    {
        if (!array_key_exists($this->_saltFieldName, $dataSet))
        {
            throw new \Bluefin\Exception\InvalidOperationException(
                "Field \"{$this->_saltFieldName}\" is required while executing SaltedMd5()!"
            );
        }

        return md5($rawValue . '*' . $dataSet[$this->_saltFieldName]);
    }
}
