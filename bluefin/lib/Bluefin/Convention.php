<?php

namespace Bluefin;

/**
 * Bluefin的默认约定
 */
class Convention
{
    const CONFIG_KEYWORD_INCLUDE = '@include';
    const CONFIG_KEYWORD_DEFAULT = 'default';
    const CONFIG_KEYWORD_NULL = '_';

    const CONFIG_SECTION_APP = 'app';
    const CONFIG_SECTION_LOG = 'log';
    const CONFIG_SECTION_DB = 'db';
    const CONFIG_SECTION_CACHE = 'cache';
    const CONFIG_SECTION_SESSION = 'session';
    const CONFIG_SECTION_LOCALE = 'locale';
    const CONFIG_SECTION_AUTH = 'auth';

    const CACHE_KEY_PREFIX_LOCALE = 'lc_';

    const DEFAULT_LOCALE_REQUEST_NAME = 'lcid';
    const DEFAULT_LOCALE_VALUE = 'zh_CN';
    const DEFAULT_LOCALE_USE_SESSION = true;
    const DEFAULT_LOCALE_USE_CACHE = true;

    const DEFAULT_REQUEST_ORDER = 'CPRG';
    const DEFAULT_VIEW_RENDERER = 'twig';
    const DEFAULT_SESSION_NAMESPACE = 'app';

    const DEFAULT_AUTH_LIFETIME = 1200;

    const SESSION_CURRENT_LOCALE = '__locale';

    const FILE_TYPE_LOCALE_FILE = '.yml';

    const FEATURE_AUTO_INCREMENT_ID = 'auto_increment_id';
    const FEATURE_LOGICAL_DELETION = 'logical_deletion';
    const FEATURE_CREATE_TIMESTAMP = 'create_timestamp';

    const LOCALE_APP = 'app';
    const LOCALE_ERROR = 'error';
    const LOCALE_DICT = 'dict';
    const LOCALE_VIEW = 'view';
    const LOCALE_METADATA = 'metadata';

    const DB_EXPR_TAG = '#';
    const DB_EXPR_VT_TAG = '@';

    const STATE_CHANGED_TIME_SUFFIX = '_time';
    const STATE_CHANGED_HISTORY_SUFFIX = '_log';

    const KEYWORD_HTTP_METHOD_FORM_PARAM = '_method';
    const KEYWORD_REQUEST_ROUTE = '_r';
    const KEYWORD_REQUEST_FROM = '_from';
    const KEYWORD_REQUEST_EVENT = '_event';
    const KEYWORD_REQUEST_ERROR = '_errorno';
    const KEYWORD_REQUEST_PARAMS = '_param';
    const KEYWORD_ALL_ROLES = '*all*';
    const KEYWORD_SYSTEM_ROLE = 'role.system';
    const KEYWORD_VENDOR_ROLE = 'role.vendor';

    const DELIMITER_MODIFIER = '|';

    const MODIFIER_NAMING_PASCAL = 'P';
    const MODIFIER_NAMING_CAMEL = 'C';
    const MODIFIER_NAMING_UPPER = 'U';
    const MODIFIER_NAMING_LOWER = 'L';
    const MODIFIER_DEFAULT_VALUE = '';
    const MODIFIER_TRIM = 'trim';
    const MODIFIER_DATE_FORMAT = 'date';
    const MODIFIER_MD5_SALT = 'md5_salt';
    const MODIFIER_MD5 = 'md5';
    const MODIFIER_TRANSLATE = 'trans';
    const MODIFIER_CONTEXT = 'context';
    const MODIFIER_PREPEND = '$.';
    const MODIFIER_CONCAT = '.';
    const MODIFIER_JSON = 'json';
    const MODIFIER_YAML = 'yaml';
    const MODIFIER_META = 'meta';

    const MODIFIER_PARAMETER_DELIMITER = '=';

    const BLUEFIN_NAMESPACE = 'Bluefin';
    const BLUEFIN_VIEW_CONTROLLER = 'view';

    private static $__namingWhileList;

    public static function getPascalNaming($phrase, $default = null)
    {
        if (!isset(self::$__namingWhileList))
        {
            self::$__namingWhileList = array(
                'oauth' => 'OAuth',
                'oauth2' => 'OAuth2',
                'uid' => 'UID',
                'mysql' => 'MySQL',
                'sql' => 'SQL',
                'uuid' => 'UUID',
                'ip' => 'IP',
                'id' => 'ID',
                'isp' => 'ISP',
                'idc' => 'IDC',
                'html' => 'HTML',
                'xml' => 'XML',
            );

            $naming = null;

            if (file_exists(CACHE . '/naming.php'))
            {
                $naming = require CACHE . '/naming.php';
            }
            else if (file_exists(APP_ETC . '/naming.yml'))
            {
                $naming = \Symfony\Component\Yaml\Yaml::load(APP_ETC . '/naming.yml');
                if (ENABLE_CACHE)
                {
                    save_var_to_php(CACHE . '/naming.php', $naming);
                }
            }

            if (!empty($naming))
            {
                self::$__namingWhileList = array_merge(self::$__namingWhileList, $naming);
            }
        }

        return array_try_get(self::$__namingWhileList, $phrase, $default);
    }

    public static function getTableAliasNaming($tableName)
    {
        if (mb_strlen($tableName) < 4) return $tableName;

        $parts = explode('_', $tableName, 3);
        $count = count($parts);

        if ($count == 1)
        {
            $alias = mb_substr($tableName, 0, 2);
        }
        else if ($count == 2)
        {
            $alias = $parts[0][0] . $parts[1][0];
        }
        else
        {
            $alias = $parts[0][0] . $parts[1][0] . $parts[2][0];
        }

        if (in_array($alias, ['in', 'on', 'and', 'or', 'not']))
        {
            $alias[mb_strlen($alias) - 1] = '1';
        }

        return $alias;
    }
}
