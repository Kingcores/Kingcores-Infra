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
define('BLUEFIN_LANCE', BLUEFIN . '/Lance');
define('BLUEFIN_BUILTIN', BLUEFIN_LANCE . '/builtin');
define('APP', ROOT . '/app');
define('APP_LIB', APP . '/lib');
define('APP_ETC', APP . '/etc');
define('APP_SERVICE', APP . '/service');
define('APP_LOCALE', APP . '/locale');
define('APP_VIEW', APP . '/view');
define('LANCE', ROOT . '/lance');
define('LANCE_EXTEND', ROOT . '/extend');
define('LANCE_EXTEND_LIB', LANCE_EXTEND . '/lib');
define('WEB_ROOT', ROOT . '/webroot');

require_once BLUEFIN_ETC . '/options.' . ENV . '.php';

// 设置包含路径
$includes = array(APP_LIB, LIB, get_include_path());
is_dir(LANCE_EXTEND_LIB) && array_unshift($includes, LANCE_EXTEND_LIB);

set_include_path(implode(PATH_SEPARATOR, $includes));

// 包含默认库
require_once BLUEFIN . '/common/common_utils.php';
require_once BLUEFIN . '/common/fs_utils.php';
require_once BLUEFIN . '/common/bluefin_conventions.php';
require_once BLUEFIN . '/common/bluefin_helpers.php';

// 设置类自动加载
spl_autoload_register('bluefin_autoload');