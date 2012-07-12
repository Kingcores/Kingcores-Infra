<?php

namespace Bluefin\Auth;
 
interface AuthInterface
{
    function getAuthUrl();
    function isAuthenticated();

    /**
     * @abstract
     * @param null $fieldName
     */
    function getAuthData($fieldName = null);
    function reloadAuthData($userName = null);
    function authenticate($userName, $password);
    function clearIdentity();

    function handleAuthenticationFailure();
    function isAuthorized($resource = null, $operation = null);
    function handleUnauthorizedAccess();
}
