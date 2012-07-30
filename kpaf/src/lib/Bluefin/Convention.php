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
    const CACHE_KEY_PREFIX_SESSION = 'ss_';

    const DEFAULT_LOCALE_REQUEST_NAME = 'lcid';
    const DEFAULT_LOCALE_VALUE = 'zh_CN';
    const DEFAULT_LOCALE_USE_SESSION = true;
    const DEFAULT_LOCALE_USE_CACHE = true;

    const DEFAULT_REQUEST_ORDER = 'PRGC';
    const DEFAULT_VIEW_RENDERER = 'twig';
    const DEFAULT_SESSION_NAMESPACE = 'default';

    const DEFAULT_AUTH_SESSION_NAMESPACE = 'auth';
    const DEFAULT_AUTH_LIFETIME = 1200;

    const DEFAULT_LOG_FORMAT = "[{%timestamp|date=c}][{%levelName}][{%channel}]{%message}\n";

    const SESSION_LIFE_COUNTER = '__init';
    const SESSION_CURRENT_LOCALE = '__locale';

    const FILE_TYPE_LOCALE_FILE = '.yml';

    const FEATURE_AUTO_INCREMENT_ID = 'auto_increment_id';
    const FEATURE_LOGICAL_DELETION = 'logical_deletion';

    const LOCALE_APP = 'app';
    const LOCALE_BLUEFIN_DOMAIN = 'bluefin';
    const LOCALE_METADATA_DOMAIN = 'metadata';

    const MSG_METHOD_NOT_IMPLEMENTED = 'Method is not implemented.';
    const MSG_METHOD_NOT_ALLOWED = 'Method is not allowed.';
    const MSG_METHOD_SHOULD_BE_OVERRIDDEN = 'Method should be overridden.';

    const KEYWORD_ROUTE_FILTERS = 'filters';

    const KEYWORD_HTTP_METHOD_FORM_PARAM = '_method';
    const KEYWORD_REQUEST_ROUTE = '_r';

    const DELIMITER_MODIFIER = '|';

    const MODIFIER_NAMING_PASCAL = 'P';
    const MODIFIER_NAMING_CAMEL = 'C';
    const MODIFIER_NAMING_UPPER = 'U';
    const MODIFIER_NAMING_LOWER = 'L';

    const MODIFIER_DEFAULT_VALUE = '';
    const MODIFIER_PARAMETER_DELIMITER = '=';

    const BLUEFIN_NAMESPACE = 'Bluefin';
    const BLUEFIN_VIEW_CONTROLLER = 'View';
    const BLUEFIN_REST_CONTROLLER = 'Rest';

    private static $__namingWhileList;

    public static function getPascalNaming($phrase, $default = null)
    {
        if (!isset(self::$__namingWhileList))
        {
            self::$__namingWhileList = array(
                'email' => 'EMail',
                'oauth' => 'OAuth',
                'oauth2' => 'OAuth2',
                'uid' => 'UID',
                'mysql' => 'MySQL',
                'sql' => 'SQL',
                'uuid' => 'UUID',
                'ip' => 'IP',
                'id' => 'ID',
                'isp' => 'ISP',
                'idc' => 'IDC'
            );

            if (file_exists(APP_ETC . '/naming.php'))
            {
                $naming = require APP_ETC . '/naming.php';
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
            return mb_substr($tableName, 0, 2);
        }
        else if ($count == 2)
        {
            return $parts[0][0] . $parts[1][0];
        }
        else
        {
            return $parts[0][0] . $parts[1][0] . $parts[2][0];
        }
    }
}
