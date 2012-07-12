<?php

namespace Bluefin\Data\Filter;

use Bluefin\Data\Model;
 
interface FilterInterface
{
    /**
     * @abstract
     * @param \Bluefin\Data\Model $model
     * @return array Returns filtered data array.
     */
    function apply(FilterContext $context);
}
