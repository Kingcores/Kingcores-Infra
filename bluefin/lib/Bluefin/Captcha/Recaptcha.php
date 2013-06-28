<?php

namespace Bluefin\Captcha;

use Bluefin\App;

class Recaptcha implements CaptchaInterface
{
    public function getCaptchaHTML()
    {
        require_once('Recaptcha/recaptchalib.php');

        $publicKey = _C('config.recaptcha.publicKey');

        App::assert(isset($publicKey), "'config.recaptcha.publicKey' is required.");

        return recaptcha_get_html($publicKey);
    }

    public function validateCaptchaInput(array $post)
    {
        require_once('Recaptcha/recaptchalib.php');

        $privateKey = _C('config.recaptcha.privateKey');

        if (!all_keys_exists(['recaptcha_challenge_field', 'recaptcha_response_field'], $post))
        {
            throw new \Bluefin\Exception\InvalidRequestException(
                _APP_("Captcha input is required.")
            );
        }

        $res = recaptcha_check_answer($privateKey,
            $_SERVER["REMOTE_ADDR"],
            $post["recaptcha_challenge_field"],
            $post["recaptcha_response_field"]);

        return $res->is_valid;
    }
}
