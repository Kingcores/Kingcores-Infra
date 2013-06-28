<?php

namespace Bluefin\Auth;
 
interface AuthInterface
{
    function getAuthUrl($callbackUrl = null, $forceLogin = false, $state = null);
    function getResponseUrl($redirectUrl = null);
    function isAuthenticated();
    function authenticate(array $authInput);
    function getUniqueID();
    function getIdentity();
    function setIdentity($identity);
    function refresh();
    function clearIdentity();
    function getData($name = null);
    function getCaptcha();
}
