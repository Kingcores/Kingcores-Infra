<?php

namespace Bluefin\Auth;

class AuthHelper
{
    const SUCCESS = 0;
    const FAILURE = -1;
    const FAILURE_IDENTITY_NOT_FOUND = -2;
    const FAILURE_CREDENTIAL_INVALID = -3;
    const FAILURE_VERIFY_CODE_INVALID = -4;
}
