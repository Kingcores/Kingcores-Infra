<?php

namespace Common\Helper;

class UniqueIdentity
{
    public static function generate($len)
    {
        $parts = explode('-', uuid_gen());
        $token = '';

        foreach ($parts as $part)
        {
            $token .= alphalize(hexdec($part));
        }

        $tokenLen = $len - strlen($token);

        if ($tokenLen > 0)
        {
            if (file_exists('/dev/urandom'))
            { // Get 100 bytes of random data
                $randomData = file_get_contents('/dev/urandom', false, null, 0, 100) . uniqid(mt_rand(), true);
            }
            else
            {
                $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
            }

            $token .= substr(hash('sha512', $randomData), 0, $tokenLen);
        }

        return $token;
    }
}
