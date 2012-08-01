<?php

namespace Bluefin\Exception;

class PageNotFoundException extends RequestException
{
    public function __construct($url = null)
    {
        isset($url) || ($url = _U());

        parent::__construct(
            _T(
                'Page not found. URL: %url%',
                \Bluefin\Convention::LOCALE_BLUEFIN_DOMAIN,
                array('%url%' => $url)
            ),
            null,
            \Bluefin\Common::HTTP_NOT_FOUND
        );
    }
}
