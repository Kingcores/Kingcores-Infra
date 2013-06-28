<?php

namespace QQWeibo;


/**
 * HTTP请求类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20
 */
class Http
{
    /**
     * 发起一个HTTP/HTTPS的请求
     * @param $url 接口的URL
     * @param $params 接口参数   array('content'=>'test', 'format'=>'json');
     * @param $method 请求类型    GET|POST
     * @param $multi 图片信息
     * @param $extheaders 扩展的包头信息
     * @return string
     */
    public static function request( $url , $params = array(), $method = 'GET' , $multi = false, $extheaders = array())
    {
        if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK OAuth2.0');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ci, CURLOPT_TIMEOUT, 3);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$extheaders;
        switch ($method)
        {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params))
                {
                    if($multi)
                    {
                        foreach($multi as $key => $file)
                        {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    }
                    else
                    {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params))
                {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers)
        {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }

        $response = curl_exec($ci);
        curl_close ($ci);
        return $response;
    }
}

/**
 * 公共函数类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20 *
 */
class Common
{
    //获取客户端IP
    public static function getClientIp()
    {
        if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
            $ip = getenv ( "HTTP_CLIENT_IP" );
        else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
            $ip = getenv ( "HTTP_X_FORWARDED_FOR" );
        else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
            $ip = getenv ( "REMOTE_ADDR" );
        else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
            $ip = $_SERVER ['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return ($ip);
    }
}

/**
 * Openid & Openkey签名类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20
 */
class SnsSign
{
    /**
     * 生成签名
     * @param string    $method 请求方法 "get" or "post"
     * @param string    $url_path
     * @param array     $params 表单参数
     * @param string    $secret 密钥
     */
    public static function makeSig($method, $url_path, $params, $secret)
    {
        $mk = self::makeSource ( $method, $url_path, $params );
        $my_sign = hash_hmac ( "sha1", $mk, strtr ( $secret, '-_', '+/' ), true );
        $my_sign = base64_encode ( $my_sign );
        return $my_sign;
    }

    private static function makeSource($method, $url_path, $params)
    {
        ksort ( $params );
        $strs = strtoupper($method) . '&' . rawurlencode ( $url_path ) . '&';
        $str = "";
        foreach ( $params as $key => $val ) {
            $str .= "$key=$val&";
        }
        $strc = substr ( $str, 0, strlen ( $str ) - 1 );
        return $strs . rawurlencode ( $strc );
    }
}

/**
 * OAuth授权类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20
 */
class OAuth
{
    public $code;
    public $uid;
    public $openkey;
    public $access_token;
    public $refresh_token;
    public $expires_in;
    public $client_id = '';
    public $client_secret = '';

    //接口url
    const apiUrlHttp = 'http://open.t.qq.com/api/';
    const apiUrlHttps = 'https://open.t.qq.com/api/';

    const accessTokenURL = 'https://open.t.qq.com/cgi-bin/oauth2/access_token';
    const authorizeURL = 'https://open.t.qq.com/cgi-bin/oauth2/authorize';

    /**
     * 初始化
     * @param $client_id 即 appid
     * @param $client_secret 即 appkey
     * @return
     */
    public  function __construct($client_id, $client_secret, $access_token, $uid)
    {
        if (!$client_id || !$client_secret) exit('client_id or client_secret is null');
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->access_token = $access_token;
        $this->uid = $uid;
    }

    /**
     * 获取授权URL
     * @param $redirect_uri 授权成功后的回调地址，即第三方应用的url
     * @param $response_type 授权类型，为code
     * @param $wap 用于指定手机授权页的版本，默认PC，值为1时跳到wap1.0的授权页，为2时同理
     * @return string
     */
    public function getAuthorizeURL($redirect_uri, $response_type = 'code', $wap = false)
    {
        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => $response_type,
            'type' => $wap
        );
        return OAuth::authorizeURL.'?'.http_build_query($params);
    }

    /**
     * 获取请求token的url
     * @param $code 调用authorize时返回的code
     * @param $redirect_uri 回调地址，必须和请求code时的redirect_uri一致
     * @return string
     */
    public  function getAccessToken($code, $uid, $redirect_uri)
    {
        $this->code = $code;
        $this->uid = $uid;


        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri
        );

        $url =  OAuth::accessTokenURL.'?'.http_build_query($params);

        $r = Http::request($url);
        parse_str($r, $out);
        if ($out['access_token']) {
            $this->access_token = $out['access_token'];
            $this->refresh_token = $out['refresh_token'];
            $this->expires_in = $out['expires_in'];
            $out['uid'] = $this->uid;
        }
        return $out;
    }

    public function api($command, $params = array(), $method = 'GET', $multi = false)
    {
        //鉴权参数
        $params['access_token'] = $this->access_token;
        $params['oauth_consumer_key'] = $this->client_id;
        $params['openid'] =  $this->uid;
        $params['format'] = 'json';
        $params['oauth_version'] = '2.a';
        $params['clientip'] = Common::getClientIp();
        $params['scope'] = 'all';
        $params['appfrom'] = 'php-sdk2.0beta';
        $params['seqid'] = time();
        $params['serverip'] = $_SERVER['SERVER_ADDR'];

        $url = OAuth::apiUrlHttps . trim($command, '/');

        //请求接口
        $r = Http::request($url, $params, $method, $multi);
        $r = preg_replace('/[^\x20-\xff]*/', "", $r); //清除不可见字符
        $r = iconv("utf-8", "utf-8//ignore", $r); //UTF-8转码
        $res=  json_decode($r,true);
        return $res;
    }

}


/**
 * 新浪微博操作类V2
 *
 * 使用前需要先手工调用saetv2.ex.class.php <br />
 *
 * @package sae
 * @author Easy Chen, Elmer Zhang,Lazypeople
 * @version 1.0
 */
class QQWeiboClient
{
    public $oauth;

    /**
     * 构造函数
     *
     * @access public
     * @param mixed $akey 微博开放平台应用APP KEY
     * @param mixed $skey 微博开放平台应用APP SECRET
     * @param mixed $access_token OAuth认证返回的token
     * @param mixed $refresh_token OAuth认证返回的token secret
     * @return void
     */
    function __construct( $akey, $skey, $access_token,$uid)
    {
        $this->oauth = new OAuth( $akey, $skey, $access_token, $uid);
    }


    /*
     *  参数名称
 描述
 format
 返回数据的格式（json或xml）
 pageflag
 分页标识（0：第一页，1：向下翻页，2：向上翻页）
 pagetime
 本页起始时间（第一页：填0，向上翻页：填上一次请求返回的第一条记录时间，向下翻页：填上一次请求返回的最后一条记录时间）
 reqnum
 每次请求记录的条数（1-70条）
 type
 拉取类型（需填写十进制数字）
0x1 原创发表 0x2 转载 如需拉取多个类型请使用|，如(0x1|0x2)得到3，则type=3即可，填零表示拉取所有类型


 contenttype
 内容过滤。0-表示所有类型，1-带文本，2-带链接，4-带图片，8-带视频，0x10-带音频 建议不使用contenttype为1的类型，如果要拉取只有文本的微博，建议使用0x80
     */
    function home_timeline( $pageflag= 0, $reqnum = 50, $contenttype = 0 )
    {
        $params = array();
        $params['pageflag'] = intval($pageflag);
        $params['reqnum'] = intval($reqnum);
        $params['contenttype'] = intval($contenttype);
        return $this->oauth->api('statuses/home_timeline', $params);
    }

    /**
    http://wiki.open.t.qq.com/index.php/API%E6%96%87%E6%A1%A3/%E5%B8%90%E6%88%B7%E7%9B%B8%E5%85%B3/%E8%8E%B7%E5%8F%96%E8%87%AA%E5%B7%B1%E7%9A%84%E8%AF%A6%E7%BB%86%E8%B5%84%E6%96%99
     */
    function user_info(  )
    {
        $params = array();
        return $this->oauth->api('user/info', $params);
    }

    // 获取他人资料信息
    // http://wiki.open.t.qq.com/index.php/API%E6%96%87%E6%A1%A3/%E5%B8%90%E6%88%B7%E7%9B%B8%E5%85%B3/%E8%8E%B7%E5%8F%96%E5%85%B6%E4%BB%96%E4%BA%BA%E8%B5%84%E6%96%99
    function user_info_by_name($name)
    {
        $params = array();
        $params['name'] = $name;
        return $this->oauth->api('user/other_info', $params);
    }

    function user_info_by_openid($openid)
    {
        $params = array();
        $params['openid'] = $openid;
        return $this->oauth->api('user/other_info', $params);
    }

}

