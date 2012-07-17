<?php

if (!defined('ENV'))
{
    define('ENV', 'dev');
}

// 设置Bluefin版本标记
define('BLUEFIN_VERSION', '1.0');

// 设置路径常量
define('ROOT', realpath(__DIR__ . '/../..'));
define('LIB', ROOT . '/lib');
define('CACHE', ROOT . '/cache');
define('TMP', ROOT . '/tmp');
define('BLUEFIN', LIB . '/Bluefin');
define('BLUEFIN_ETC', BLUEFIN . '/etc');
define('BLUEFIN_BUILTIN', BLUEFIN . '/builtin');
define('BLUEFIN_LANCE', BLUEFIN . '/Lance');
define('APP', ROOT . '/app');
define('APP_LIB', APP . '/lib');
define('APP_ETC', APP . '/etc');
define('APP_SERVICE', APP . '/service');
define('APP_LOCALE', APP . '/locale');
define('APP_VIEW', APP . '/view');
define('APP_EXTEND', APP . '/extend');
define('LANCE', ROOT . '/lance');
define('WEB_ROOT', ROOT . '/www');

require_once BLUEFIN_ETC . '/options.' . ENV . '.php';

// 设置包含路径
$includes = array(APP_LIB, LIB, get_include_path());
file_exists(APP_EXTEND) && array_unshift($includes, APP_EXTEND);

set_include_path(implode(PATH_SEPARATOR, $includes));

// 包含默认库
require_once BLUEFIN . '/common/common_utils.php';
require_once BLUEFIN . '/common/fs_utils.php';
require_once BLUEFIN . '/common/bluefin_conventions.php';
require_once BLUEFIN . '/common/bluefin_helpers.php';

// 设置类自动加载
spl_autoload_register('bluefin_autoload');