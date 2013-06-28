<?php

namespace Bluefin\Lance;

class Site
{
    private $_siteName;
    private $_sitemap;

    public function __construct($siteName, array $siteConfig)
    {
        $this->_siteName = $siteName;

        $this->_sitemap = array_try_get($siteConfig, 'sitemap', array());
    }

    public function getSiteName()
    {
        return $this->_siteName;
    }

    public function getSitemap()
    {
        return $this->_sitemap;
    }


}
