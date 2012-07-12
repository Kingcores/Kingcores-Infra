<?php

define('ENABLE_CACHE', true);
define('ASSERT_BEHAVIOR', 'throw'); //disable, throw, error, ignore
define('RENDER_EXCEPTION', false);
define('ENABLE_LOCALE_EXPORT', false);
define('ENABLE_DEBUG_CHECK', false);

error_reporting(E_ALL ^ E_NOTICE);