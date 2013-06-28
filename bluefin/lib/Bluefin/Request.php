<?php

namespace Bluefin;

use Bluefin\Convention;

/**
 * HTTP请求类
 */
class Request
{
    const SCOPE_HEADER = 0x1;
    const SCOPE_POST = 0x2;
    const SCOPE_ROUTE = 0x4;
    const SCOPE_GET = 0x8;
    const SCOPE_COOKIE = 0x10;
    const SCOPE_PUT = 0x20;

    const SCOPE_ALL = 0xff;

    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @return Request A new request
     */
    static public function createFromGlobals()
    {
        return new self($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    protected $_get;
    protected $_post;
    protected $_put;
    protected $_query;
    protected $_server;
    protected $_files;
    protected $_cookie;
    protected $_route;

    private $_requestOrder;
    private $_cache;

    /**
     * Constructor.
     */
    public function __construct(array $query = array(), array $request = array(), array $cookie = array(), array $files = array(), array $server = array(), $content = null)
    {
        $this->_get = $query;
        $this->_post = $request;
        $this->_cookie = $cookie;
        $this->_files = $files;
        $this->_server = $server;
        $this->_route = array();

        // Order of precedence: 1. POST, 2.ROUTE, 3. GET, 4. COOKIE
        $this->_requestOrder = Convention::DEFAULT_REQUEST_ORDER;
        $this->_cache = array();
    }

    /**
     * Clones the current request.
     */
    public function __clone()
    {
        App::assert(false, "Not supported!");
    }

    public function setRequestOrder($order)
    {
        $this->_requestOrder = $order;
    }

    public function getRequestOrder()
    {
        return $this->_requestOrder;
    }

    public function setRouteParams($params)
    {
        $this->_route = $params;
    }

    public function has($paramName, $scope = self::SCOPE_ALL)
    {
        if ((self::SCOPE_GET & $scope) == self::SCOPE_GET && array_key_exists($paramName, $this->_get))
        {
            return true;
        }

        if ((self::SCOPE_POST & $scope) == self::SCOPE_POST && array_key_exists($paramName, $this->_post))
        {
            return true;
        }

        if ((self::SCOPE_ROUTE & $scope) == self::SCOPE_ROUTE && array_key_exists($paramName, $this->_route))
        {
            return true;
        }

        if ((self::SCOPE_COOKIE & $scope) == self::SCOPE_COOKIE && array_key_exists($paramName, $this->_cookie))
        {
            return true;
        }

        if ((self::SCOPE_HEADER & $scope) == self::SCOPE_HEADER && $this->hasHttpHeader($paramName))
        {
            return true;
        }

        if ((self::SCOPE_PUT & $scope) == self::SCOPE_PUT && $this->isPut() && !is_null($this->getPutParam($paramName)))
        {
            return true;
        }

        return false;
    }

    /**
     * 根据请求参数源的优先次序返回请求参数。
     * 速度很慢，尽量不调用此方法。
     *
     * @param string $key 参数名称
     * @param null $default 默认值
     * @param null $order 顺序
     * @return mixed 参数值
     */
    public function get($key, $default = null, $order = null)
    {
        isset($order) || ($order = $this->_requestOrder);

        $l = strlen($order);
        for ($i = 0; $i < $l; ++$i)
        {
            $requestToken = $order[$i];

            if ($requestToken === 'G' && array_key_exists($key, $this->_get))
            {
                $value = $this->_get[$key];
                return is_array($value) ? $value : urldecode($value);
            }

            if ($requestToken === 'R' && array_key_exists($key, $this->_route))
                return $this->_route[$key];

            if ($requestToken === 'P' && array_key_exists($key, $this->_post))
                return $this->_post[$key];

            if ($requestToken === 'C' && array_key_exists($key, $this->_cookie))
                return $this->_cookie[$key];
        }

        return $default;
    }

    public function getQueryParam($name, $default = null, $pop = false)
    {
        $value = array_try_get($this->_get, $name, $default, $pop);

        if (isset($value) && !is_array($value))
        {
            return urldecode($value);
        }

        return $value;
    }

    public function getQueryParams()
    {
        return $this->_get;
    }

    public function getRouteParam($name, $default = null)
    {
        return array_try_get($this->_route, $name, $default);
    }

    public function setRouteParam($name, $value)
    {
        $this->_route[$name] = $value;
    }

    public function getRouteParams()
    {
        return $this->_route;
    }

    public function getPostParam($name, $default = null)
    {
        return is_array($name) ? array_get_all($this->_post, $name) : array_try_get($this->_post, $name, $default);
    }

    public function getPostParams()
    {
        return $this->_post;
     }

    public function getPutParam($name, $default = null)
    {
        $put = $this->getPutParams();
        return array_try_get($put, $name, $default);
    }

    public function getPutParams()
    {
        if (!isset($this->_put))
        {
            mb_parse_str($this->getRawBody(), $this->_put);
        }

        return $this->_put;
    }

    public function getCookieParam($name, $default = null)
    {
        return array_try_get($this->_cookie, $name, $default);
    }

    public function getCookieParams()
    {
        return $this->_cookie;
    }

    public function getServerParam($name, $default = null)
    {
        return array_try_get($this->_server, $name, $default);
    }

    public function getServerParams()
    {
        return $this->_server;
    }

    public function hasHttpHeader($name)
    {
        if (array_key_exists(Common::HTTP_HEADER_PREFIX . strtoupper($name), $this->_server))
        {
            return true;
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers'))
        {
            $headers = apache_request_headers();
            if (array_key_exists($name, $headers))
            {
                return true;
            }

            $header = strtolower($name);
            foreach ($headers as $key => $value)
            {
                if (strtolower($key) == $header)
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function getHttpHeader($name, $default = null)
    {
        $headerName = Common::HTTP_HEADER_PREFIX . strtoupper($name);
        $value = $this->getServerParam($headerName);

        if (isset($value)) return $value;

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers'))
        {
            $headers = apache_request_headers();
            if (isset($headers[$name]))
            {
                return $headers[$name];
            }

            $header = strtolower($name);
            foreach ($headers as $key => $value)
            {
                if (strtolower($key) == $header)
                {
                    return $value;
                }
            }
        }

        return $default;
    }

    public function isSecure()
    {
        if (array_key_exists('is_ssl', $this->_cache))
        {
            return $this->_cache['is_ssl'];
        }

        $https = $this->getServerParam('HTTPS');

        if (strtolower($https) == 'on' || $https == 1)
        {
            $this->_cache['is_ssl'] = true;
            return true;
        }
        else
        {
            $https = $this->getHttpHeader('SSL_HTTPS');
            if (strtolower($https) == 'on' || $https == 1)
            {
                $this->_cache['is_ssl'] = true;
                return true;
            }
        }

        $this->_cache['is_ssl'] = false;

        return false;
    }

    public function isXmlHttpRequest()
    {
        return 'XMLHttpRequest' == array_try_get($this->_server, 'X-Requested-With');
    }

    /**
     * Returns the client IP address.
     * @return string The client IP address
     */
    public function getClientIP()
    {
        if (array_key_exists('client_ip', $this->_cache))
        {
            return $this->_cache['client_ip'];
        }

        return ($this->_cache['client_ip'] = $this->getHttpHeader(
            'CLIENT_IP',
            $this->getHttpHeader(
                'X_FORWARDED_FOR',
                $this->getServerParam('REMOTE_ADDR')
            )
        ));
    }

    public function getReferer()
    {
        return $this->getHttpHeader('REFERER');
    }

    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Returns the host name.
     *
     * @return string
     */
    public function getHost()
    {
        if (array_key_exists('host', $this->_cache))
        {
            return $this->_cache['host'];
        }

        $host = $this->getHttpHeader(
            'HOST',
            $this->getServerParam(
                'SERVER_NAME',
                $this->getServerParam('SERVER_ADDR')
            )
        );       

        // Remove port number from host
        $hostParts = explode(':', $host);
        $this->_cache['host'] = trim($hostParts[0]);

        return $this->_cache['host'];
    }

    public function getPort()
    {
        return $this->getServerParam('SERVER_PORT');
    }

    /**
     * Returns the HTTP host being requested.
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * @return string
     */
    public function getHttpHost()
    {
        if (array_key_exists('http_host', $this->_cache))
        {
            return $this->_cache['http_host'];
        }

        $httpHost = $this->getHttpHeader('HOST');
        if (empty($httpHost))
        {
            $scheme = $this->getScheme();
            $name   = $this->getHost();
            $port   = $this->getPort();

            if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443))
            {
                $httpHost = $name;
            }
            else
            {
                $httpHost = $name.':'.$port;
            }
        }

        return ($this->_cache['http_host'] = $httpHost);
    }

    public function getQueryString()
    {
        return $this->getServerParam('QUERY_STRING', '');
    }

    /**
     * 对query string总的参数进行排序，重新组装。
     * @return array
     */
    public function getNormalizedQueryString()
    {
        if (array_key_exists('query_string', $this->_cache))
        {
            return $this->_cache['query_string'];
        }

        $qs = $this->getQueryString();
        if ($qs != '')
        {
            $parts = array();
            $order = array();

            foreach (explode('&', $qs) as $segment)
            {
                if (false === strpos($segment, '='))
                {
                    $parts[] = $segment;
                    $order[] = $segment;
                }
                else
                {
                    $tmp = explode('=', urldecode($segment), 2);
                    $parts[] = urlencode($tmp[0]).'='.urlencode($tmp[1]);
                    $order[] = $tmp[0];
                }
            }

            array_multisort($order, SORT_ASC, $parts);
            $qs = implode('&', $parts);
        }
        return $this->_cache['query_string'] = $qs;
    }

    /**
     * http://localhost:88/web/index.php?xxx=yyy    return '/web/index.php?xxx=yyy'
     * @return mixed|null|string
     */
    public function getRequestUri()
    {
        if (array_key_exists('request_uri', $this->_cache))
        {
            return $this->_cache['request_uri'];
        }

        // check this first so IIS will catch
        $requestUri = $this->getHttpHeader('X_REWRITE_URL');

        if (!isset($requestUri))
        {
            $requestUri = $this->getServerParam('REQUEST_URI');

            // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
            $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0)
            {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        }

        return ($this->_cache['request_uri'] = $requestUri);
    }

    public function rebuildRequestUri($uri)
    {
        unset($this->_cache['request_uri']);
        unset($this->_cache['req_path']);
        $this->_cache['request_uri'] = $uri;
    }

    public function getFullRequestUri()
    {
        return $this->getScheme() . '://' . $this->getHttpHost() . $this->getRequestUri();
    }

    public function getRequestPath()
    {
        if (array_key_exists('req_path', $this->_cache))
        {
            return $this->_cache['req_path'];
        }

        $baseUrl = $this->getRequestUri();
        if (empty($baseUrl))
        {
            return ($this->_cache['rel_path'] = '');
        }

        $qsPos = strrpos($baseUrl, '?');
        if (false !== $qsPos)
        {
            $baseUrl = substr($baseUrl, 0, $qsPos);
        }

        $basePath = dirname($baseUrl);

        if (substr(PHP_OS, 0, 3) === 'WIN')
        {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return ($this->_cache['req_path'] = rtrim($basePath, '/'));
    }

    /**
     * Gets relative script url.
     * Generally, it's the relative path of index.php
     *
     * @return string
     */
    public function getScriptRelativeUrl()
    {
        if (array_key_exists('rel_script', $this->_cache))
        {
            return $this->_cache['rel_script'];
        }

        $filename = (isset($this->_server['SCRIPT_FILENAME'])) ? basename($this->_server['SCRIPT_FILENAME']) : '';
        $baseUrl = '';

        if (isset($this->_server['SCRIPT_NAME']) && basename($this->_server['SCRIPT_NAME']) === $filename)
        {
            $baseUrl = $this->_server['SCRIPT_NAME'];
        }
        else if (isset($this->_server['PHP_SELF']) && basename($this->_server['PHP_SELF']) === $filename)
        {
            $baseUrl = $this->_server['PHP_SELF'];
        }
        else
        {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path    = isset($this->_server['PHP_SELF']) ? $this->_server['PHP_SELF'] : '';
            $file    = isset($this->_server['SCRIPT_FILENAME']) ? $this->_server['SCRIPT_FILENAME'] : '';
            $segs    = explode('/', trim($file, '/'));
            $segs    = array_reverse($segs);
            $index   = 0;
            $last    = count($segs);

            // @codingStandardsIgnoreStart
            do {
                $seg     = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
                $pos = strpos($path, $baseUrl);
            } while (($last > $index) && (false !== $pos) && (0 != $pos));
            // @codingStandardsIgnoreEnd
        }

        return ($this->_cache['rel_script'] = rtrim($baseUrl, '/'));
    }

    public function getScriptUrl()
    {
        $qs = $this->getQueryString();
        if ('' !== $qs)
        {
            $qs = '?' . $qs;
        }

        return $this->getScheme() . '://' . $this->getHttpHost() . $this->getScriptRelativeUrl() . $qs;
    }

    /**
     * Returns the base url path from which this request is executed.
     *
     * Suppose that an index.php file instantiates this request object:
     *
     *  * http://localhost/index.php        returns 'http://localhost'
     *  * http://localhost/index.php/page   returns 'http://localhost'
     *  * http://localhost/web/index.php    return 'http://localhost/web'
     *
     * @return string
     */
    public function getScriptAbsPath()
    {
        return $this->getScheme() . '://' . $this->getHttpHost() . $this->getScriptRelativePath();
    }

    /**
     * Returns the root path from which this request is executed.
     *
     * Suppose that an index.php file instantiates this request object:
     *
     *  * http://localhost/index.php        returns ''
     *  * http://localhost/index.php/page   returns ''
     *  * http://localhost/web/index.php    return '/web'
     *
     * @return string
     */
    public function getScriptRelativePath()
    {
        if (array_key_exists('rel_path', $this->_cache))
        {
            return $this->_cache['rel_path'];
        }

        $filename = (isset($this->_server['SCRIPT_FILENAME']))
                  ? basename($this->_server['SCRIPT_FILENAME'])
                  : '';

        $baseUrl = $this->getScriptRelativeUrl();
        if (empty($baseUrl))
        {
            return ($this->_cache['rel_path'] = '');
        }

        if (basename($baseUrl) === $filename)
        {
            $basePath = dirname($baseUrl);
        }
        else
        {
            $basePath = $baseUrl;
        }

        if (substr(PHP_OS, 0, 3) === 'WIN')
        {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return ($this->_cache['rel_path'] = rtrim($basePath, '/'));
    }

    /**
     * Gets the request method.
     *
     * @return string The request method
     */
    public function getMethod()
    {
        if (array_key_exists('request_method', $this->_cache))
        {
            return $this->_cache['request_method'];
        }

        $this->_cache['request_method'] = strtoupper($this->getServerParam('REQUEST_METHOD', Common::HTTP_METHOD_GET));

        return $this->_cache['request_method'];
    }

    /**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost()
    {
        return Common::HTTP_METHOD_POST == $this->getMethod();
    }

    /**
     * Was the request made by GET?
     *
     * @return boolean
     */
    public function isGet()
    {
        return Common::HTTP_METHOD_GET == $this->getMethod();
    }

    /**
     * Was the request made by PUT?
     *
     * @return boolean
     */
    public function isPut()
    {
        return Common::HTTP_METHOD_PUT == $this->getMethod() ||
               ($this->isPost() && Common::HTTP_METHOD_PUT == strtoupper($this->getPostParam(Convention::KEYWORD_HTTP_METHOD_FORM_PARAM)));
    }

    /**
     * Was the request made by DELETE?
     *
     * @return boolean
     */
    public function isDelete()
    {
        return Common::HTTP_METHOD_DELETE == $this->getMethod() ||
               ( $this->isPost() && Common::HTTP_METHOD_DELETE == strtoupper($this->getPostParam(Convention::KEYWORD_HTTP_METHOD_FORM_PARAM)));
    }

    /**
     * Was the request made by HEAD?
     *
     * @return boolean
     */
    public function isHead()
    {
        return Common::HTTP_METHOD_HEAD == $this->getMethod();
    }

    /**
     * Was the request made by OPTIONS?
     *
     * @return boolean
     */
    public function isOptions()
    {
        return Common::HTTP_METHOD_OPTIONS == $this->getMethod();
    }

    /**
     * Return the raw body of the request, if present
     *
     * @return string Raw body, or false if not present
     */
    public function getRawBody()
    {
        if (array_key_exists('raw_body', $this->_cache))
        {
            return $this->_cache['raw_body'];
        }

        $body = file_get_contents('php://input');

        return ($this->_cache['raw_body'] = trim($body));
    }

    public function getAcceptLanguages()
    {
        if (array_key_exists('accept_langs', $this->_cache))
        {
            return $this->_cache['accept_langs'];
        }

        $languages = array();
        $httpLanguages = $this->getHttpHeader('ACCEPT_LANGUAGE');
        
        if (isset($httpLanguages))
        {
            $values = array();
            foreach (array_filter(explode(',', $httpLanguages)) as $value)
            {
                // Cut off any q-value that might come after a semi-colon
                $pos = strpos($value, ';');
                if (false !== $pos)
                {
                    $q = (float) trim(substr($value, strpos($value, '=') + 1));
                    $value = trim(substr($value, 0, $pos));
                }
                else
                {
                    $q = 1;
                }

                if (0 < $q)
                {
                    $values[trim($value)] = $q;
                }
            }

            arsort($values);

            $rawLanguages = array_keys($values);

            foreach ($rawLanguages as $lang)
            {
                if (strstr($lang, '-'))
                {
                    $codes = explode('-', $lang);
                    if ($codes[0] == 'i')
                    {
                        // Language not listed in ISO 639 that are not variants
                        // of any listed language, which can be registered with the
                        // i-prefix, such as i-cherokee
                        if (count($codes) > 1)
                        {
                            $lang = $codes[1];
                        }
                    }
                    else
                    {
                        $max = count($codes);
                        for ($i = 0; $i < $max; $i++)
                        {
                            if ($i == 0)
                            {
                                $lang = strtolower($codes[0]);
                            }
                            else
                            {
                                $lang .= '_'.strtoupper($codes[$i]);
                            }
                        }
                    }
                }

                $languages[] = $lang;
            }
        }

        return $this->_cache['accept_langs'] = $languages;
    }
}
