<?php

namespace Bluefin\Data\Filter;

interface ValueFilterInterface
{
    /**
     * @abstract
     * @param $name
     * @param $value
     * @param $context
     * @return mixed
     */
    function apply($name, $value, $context);    
    /**
     * @abstract
     * @return boolean
     */
    function isFailed();
    /**
     * @abstract
     * @return string
     */
    function getMessage();
}
