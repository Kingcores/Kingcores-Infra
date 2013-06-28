<?php

namespace Upyun;

class WeibotuiUpyun
{
    /** 上传本地图片到又拍云
     * @static
     * @param $fileName
     * @return null|string : 如果上传成功，返回upyun中的文件url地址，否则返回null
     */
    public static function  uploadImage($fileName)
    {
        require_once('upyun.class.php');

        /// 初始化空间
        $upyun = new \UpYun("weibotui", "weibotui", "0dgW2SAJwd");

        //print('SDK 版本 '.$upyun->version()."\n");
        /// 设置是否打印调试信息, 当 debug == false 时所有文件操作错误调试都将跳过，不会中断当前程序执行
        $upyun->debug = false;

        /// 切换 API 接口的域名
        /// {默认 v0.api.upyun.com 自动识别, v1.api.upyun.com 电信, v2.api.upyun.com 联通, v3.api.upyun.com 移动}
        $upyun->setApiDomain('v1.api.upyun.com');

        // 按年月日划分目录
        $fileDir = date('/Y/m/d/');

        // 随机文件名
        $rand1 = sprintf("%05u", rand(0,99999));
        $rand2 = sprintf("%05u", rand(0,99999));
        $randomStr = date('His') . $rand1 .$rand2;
        $fullFileName = $fileDir . $randomStr  . self::getFileExtName($fileName);
        $autoCreateDir = true;

        // 采用数据流模式上传文件（可节省内存）
        if(strstr($fileName,'http://'))
        {
            ob_start();

            readfile($fileName);

            $binary = ob_get_clean();

            $result =  $upyun->writeFile($fullFileName, $binary, $autoCreateDir);
        }
        else
        {
            $fh = fopen($fileName,'r');
            $result =  $upyun->writeFile($fullFileName, $fh, $autoCreateDir);
            fclose($fh);
        }


        if($result)
        {
            return 'http://img.weibotui.com' . $fullFileName ;
        }else
        {
            return null;
        }
    }

    /** 根据又拍云的图片url地址，删除图片
     * @static
     * @param string $imageUrl 如 http://img.weibotui.com/test.png
     */
    public static function deleteImage($imageUrl)
    {
        // todo：
    }

    /** 获取文件扩展名
     * @static
     * @param $fileName
     * @return string
     */
    private static function getFileExtName($fileName)
    {
        $fileName = basename($fileName);
        $ext = '';
        $pt = strrpos($fileName, '.');
        if ($pt)
        {
            $ext = substr($fileName, $pt, strlen($fileName) - $pt + 1);
        }
        return $ext;
    }
}

