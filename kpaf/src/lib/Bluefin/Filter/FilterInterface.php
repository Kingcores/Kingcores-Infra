<?php

namespace Bluefin\Filter;
 
interface FilterInterface
{
    /**
     * @abstract
     * @param $filterOptions array|null
     * @return boolean
     */
    function filter(array $filterOptions = null);
}
