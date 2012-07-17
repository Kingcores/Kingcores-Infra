<?php

namespace Bluefin\Auth\Persistence;
 
interface PersistenceInterface
{
    function isEmpty();
    function read();
    function write(array $data);
    function clear();
}
