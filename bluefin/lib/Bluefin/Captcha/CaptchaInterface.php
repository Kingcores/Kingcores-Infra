<?php

namespace Bluefin\Captcha;

interface CaptchaInterface
{
    function getCaptchaHTML();
    function validateCaptchaInput(array $post);
}
