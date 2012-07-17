<?php

namespace Bluefin\Lance\Skeleton;
 
interface BlockInterface 
{
    /**
     * @abstract
     * @return string
     */
    function renderAction();

    /**
     * @abstract
     * @return string
     */
    function renderView();
}
