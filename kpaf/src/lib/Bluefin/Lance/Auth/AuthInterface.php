<?php

namespace Bluefin\Lance\Auth;
 
interface AuthInterface
{
    function generate($authName, array $config);
}
