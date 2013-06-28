<?php

namespace Bluefin\Exception;

class PageNotFoundException extends RequestException
{
    public function __construct($url = null)
    {
        isset($url) || ($url = \Bluefin\App::getInstance()->request()->getRequestUri());

        parent::__construct(
            _T(
                'Page not found. URL: %url%',
                \Bluefin\Convention::LOCALE_APP,
                array('%url%' => $url)
            ),
            \Bluefin\Common::HTTP_NOT_FOUND
        );
    }
}
