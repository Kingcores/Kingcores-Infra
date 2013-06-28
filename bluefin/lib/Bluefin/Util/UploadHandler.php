<?php

namespace Bluefin\Util;

use Bluefin\App;

class UploadHandler
{
    protected $_app;

    protected $_options;
    // PHP File Upload error message codes:
    // http://php.net/manual/en/features.file-upload.errors.php
    protected $_error_messages = array(
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload',
        'post_max_size' => 'The uploaded file exceeds the post_max_size directive in php.ini',
        'max_file_size' => 'File is too big',
        'min_file_size' => 'File is too small',
        'accept_file_types' => 'Filetype not allowed',
        'max_number_of_files' => 'Maximum number of files exceeded',
        'max_width' => 'Image exceeds maximum width',
        'min_width' => 'Image requires a minimum width',
        'max_height' => 'Image exceeds maximum height',
        'min_height' => 'Image requires a minimum height'
    );

    public function __construct($options = null, $initialize = true)
    {
        $this->_app = App::getInstance();

        $this->_options = array(
            'script_url' => $this->_app->request()->getScriptRelativeUrl(),
            'upload_dir' => WEB_ROOT . '/upload/',
            'upload_url' => $this->_app->rootUrl() .'/upload/',
            'custom_dir' => null,
            'mkdir_mode' => 0755,
            // Set the following option to 'POST', if your server does not support
            // DELETE requests. This is a parameter sent to the client:
            'delete_type' => 'DELETE',
            'access_control_allow_origin' => '*',
            'access_control_allow_credentials' => false,
            'access_control_allow_methods' => array(
                'OPTIONS',
                'HEAD',
                'GET',
                'POST',
                'PUT',
                'PATCH',
                'DELETE'
            ),
            'access_control_allow_headers' => array(
                'Content-Type',
                'Content-Range',
                'Content-Disposition'
            ),
            // Enable to provide file downloads via GET requests to the PHP script:
            'download_via_php' => false,
            // Defines which files can be displayed inline when downloaded:
            'inline_file_types' => '/\.(gif|jpe?g|png)$/i',
            // Defines which files (based on their names) are accepted for upload:
            'accept_file_types' => '/.+$/i',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            // The maximum number of files for the upload directory:
            'max_number_of_files' => null,
            // Image resolution restrictions:
            'max_width' => null,
            'max_height' => null,
            'min_width' => 1,
            'min_height' => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads' => false,
            'image_versions' => array(
                // Uncomment the following version to restrict the size of
                // uploaded images:
                /*
                '' => array(
                    'max_width' => 1920,
                    'max_height' => 1200,
                    'jpeg_quality' => 95
                ),
                */
                // Uncomment the following to create medium sized images:
                /*
                'medium' => array(
                    'max_width' => 800,
                    'max_height' => 600,
                    'jpeg_quality' => 80
                ),
                */
                'thumbnail' => array(
                    'max_width' => 80,
                    'max_height' => 80,
                    'jpeg_quality' => 80
                )
            )
        );

        if (isset($options))
        {
            $this->_options = array_merge($this->_options, $options);
        }

        if ($initialize)
        {
            $this->initialize();
        }
    }

    protected function initialize()
    {
        switch ($this->_app->request()->getMethod())
        {
            case 'GET':
                $this->get();
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->post();
                break;
            case 'DELETE':
                $this->delete();
                break;
            case 'OPTIONS':
            case 'HEAD':
            default:
                header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    protected function _getCustomPath()
    {
        if (isset($this->_options['custom_dir']))
        {
            return str_pad_if($this->_options['custom_dir'], '/', false, true);
        }

        return '';
    }

    protected function _getUploadPath($file_name = null, $version = null)
    {
        $file_name = isset($file_name) ? $file_name : '';
        $version_path = empty($version) ? '' : $version.'/';
        return $this->_options['upload_dir'].$this->_getCustomPath()
            .$version_path.$file_name;
    }

    protected function _getQuerySeparator($url) {
        return strpos($url, '?') === false ? '?' : '&';
    }

    protected function _getDownloadUrl($file_name, $version = null)
    {
        if ($this->_options['download_via_php'])
        {
            $url = $this->_options['script_url']
                .$this->_getQuerySeparator($this->_options['script_url'])
                .'file='.rawurlencode($file_name);
            if ($version) {
                $url .= '&version='.rawurlencode($version);
            }
            return $url;
        }

        $version_path = empty($version) ? '' : rawurlencode($version).'/';

        return $this->_options['upload_url'].$this->_getCustomPath()
            .$version_path.rawurlencode($file_name);
    }

    protected function _setFileDeleteProperties($file)
    {
        $file->delete_url = $this->_options['script_url']
            .$this->_getQuerySeparator($this->_options['script_url'])
            .'file='.rawurlencode($file->name);
        $file->delete_type = $this->_options['delete_type'];
        if ($file->delete_type !== 'DELETE')
        {
            $file->delete_url .= '&_method=DELETE';
        }
        if ($this->_options['access_control_allow_credentials'])
        {
            $file->delete_with_credentials = true;
        }
    }

    protected function _getFileSize($file_path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) 
        {
            clearstatcache(true, $file_path);
        }
        
        return fix_integer_overflow(filesize($file_path));
    }

    protected function is_valid_file_object($file_name) {
        $file_path = $this->_getUploadPath($file_name);
        if (is_file($file_path) && $file_name[0] !== '.') {
            return true;
        }
        return false;
    }

    protected function get_file_object($file_name)
    {
        if ($this->is_valid_file_object($file_name))
        {
            $file = new \stdClass();
            $file->name = $file_name;
            $file->size = $this->_getFileSize(
                $this->_getUploadPath($file_name)
            );
            $file->url = $this->_getDownloadUrl($file->name);
            foreach($this->_options['image_versions'] as $version => $options) {
                if (!empty($version)) {
                    if (is_file($this->_getUploadPath($file_name, $version))) {
                        $file->{$version.'_url'} = $this->_getDownloadUrl(
                            $file->name,
                            $version
                        );
                    }
                }
            }
            $this->_setFileDeleteProperties($file);
            return $file;
        }
        return null;
    }

    protected function get_file_objects($iteration_method = 'get_file_object') {
        $upload_dir = $this->_getUploadPath();
        if (!is_dir($upload_dir)) {
            return array();
        }
        return array_values(array_filter(array_map(
            array($this, $iteration_method),
            scandir($upload_dir)
        )));
    }

    protected function count_file_objects() {
        return count($this->get_file_objects('is_valid_file_object'));
    }

    protected function _createScaledImage($file_name, $version, $options)
    {
        $file_path = $this->_getUploadPath($file_name);

        if (!empty($version))
        {
            $version_dir = $this->_getUploadPath(null, $version);
            if (!is_dir($version_dir))
            {
                mkdir($version_dir, $this->_options['mkdir_mode'], true);
            }
            $new_file_path = $version_dir.'/'.$file_name;
        }
        else
        {
            $new_file_path = $file_path;
        }

        list($img_width, $img_height) = @getimagesize($file_path);
        if (!$img_width || !$img_height)
        {
            return false;
        }

        $scale = min(
            $options['max_width'] / $img_width,
            $options['max_height'] / $img_height
        );

        if ($scale >= 1)
        {
            if ($file_path !== $new_file_path)
            {
                return copy($file_path, $new_file_path);
            }

            return true;
        }

        $new_width = $img_width * $scale;
        $new_height = $img_height * $scale;
        $new_img = @imagecreatetruecolor($new_width, $new_height);
        switch (strtolower(substr(strrchr($file_name, '.'), 1)))
        {
            case 'jpg':
            case 'jpeg':
                $src_img = @imagecreatefromjpeg($file_path);
                $write_image = 'imagejpeg';
                $image_quality = isset($options['jpeg_quality']) ?
                    $options['jpeg_quality'] : 75;
                break;
            case 'gif':
                @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
                $src_img = @imagecreatefromgif($file_path);
                $write_image = 'imagegif';
                $image_quality = null;
                break;
            case 'png':
                @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
                @imagealphablending($new_img, false);
                @imagesavealpha($new_img, true);
                $src_img = @imagecreatefrompng($file_path);
                $write_image = 'imagepng';
                $image_quality = isset($options['png_quality']) ?
                    $options['png_quality'] : 9;
                break;
            default:
                $src_img = null;
        }

        $success = $src_img && @imagecopyresampled(
            $new_img,
            $src_img,
            0, 0, 0, 0,
            $new_width,
            $new_height,
            $img_width,
            $img_height
        ) && $write_image($new_img, $new_file_path, $image_quality);
        // Free up memory (imagedestroy does not delete files):
        @imagedestroy($src_img);
        @imagedestroy($new_img);

        return $success;
    }

    protected function get_error_message($error) {
        return array_key_exists($error, $this->_error_messages) ?
            $this->_error_messages[$error] : $error;
    }

    function get_config_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return fix_integer_overflow($val);
    }

    protected function validate($uploaded_file, $file, $error, $index) {
        if ($error) {
            $file->error = $this->get_error_message($error);
            return false;
        }
        $content_length = fix_integer_overflow(intval($_SERVER['CONTENT_LENGTH']));
        $post_max_size = $this->get_config_bytes(ini_get('post_max_size'));
        if ($post_max_size && ($content_length > $post_max_size)) {
            $file->error = $this->get_error_message('post_max_size');
            return false;
        }
        if (!preg_match($this->_options['accept_file_types'], $file->name)) {
            $file->error = $this->get_error_message('accept_file_types');
            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->_getFileSize($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->_options['max_file_size'] && (
                $file_size > $this->_options['max_file_size'] ||
                $file->size > $this->_options['max_file_size'])
            ) {
            $file->error = $this->get_error_message('max_file_size');
            return false;
        }
        if ($this->_options['min_file_size'] &&
            $file_size < $this->_options['min_file_size']) {
            $file->error = $this->get_error_message('min_file_size');
            return false;
        }
        if (is_int($this->_options['max_number_of_files']) && (
                $this->count_file_objects() >= $this->_options['max_number_of_files'])
            ) {
            $file->error = $this->get_error_message('max_number_of_files');
            return false;
        }

        list($img_width, $img_height) = @getimagesize($uploaded_file);
        if (is_int($img_width)) {
            if ($this->_options['max_width'] && $img_width > $this->_options['max_width']) {
                $file->error = $this->get_error_message('max_width');
                return false;
            }
            if ($this->_options['max_height'] && $img_height > $this->_options['max_height']) {
                $file->error = $this->get_error_message('max_height');
                return false;
            }
            if ($this->_options['min_width'] && $img_width < $this->_options['min_width']) {
                $file->error = $this->get_error_message('min_width');
                return false;
            }
            if ($this->_options['min_height'] && $img_height < $this->_options['min_height']) {
                $file->error = $this->get_error_message('min_height');
                return false;
            }
        }
        return true;
    }

    protected function _getUniqueFilename($extName)
    {
        $name = str_replace('-', '', uuid_gen().'.'.$extName);

        while (file_exists($this->_getUploadPath($name)))
        {
            $name = str_replace('-', '', uuid_gen().'.'.$extName);
        }

        return $name;
    }

    protected function _getFileExtName($name, $type)
    {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $name = trim(basename(stripslashes($name)), ".\x00..\x20");
        str_replace('..', '_', $name);

        $pos = strrpos($name, '.');
        if (false !== $pos)
        {
            $name = substr($name, $pos+1);
        }

        // Add missing file extension for known image types:
        if (empty($name) && preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches))
        {
            $name = $matches[1];
        }

        return $name;
    }

    protected function _getFileName($name, $type)
    {
        return $this->_getUniqueFilename(
            $this->_getFileExtName($name, $type)
        );
    }

    protected function _handleFileUpload($uploaded_file, $name, $size, $type, $error,
            $index = null, $content_range = null)
    {
        $file = new \stdClass();
        $file->name = $this->_getFileName($name, $type);
        $file->size = fix_integer_overflow(intval($size));
        $file->type = $type;

        if ($this->validate($uploaded_file, $file, $error, $index))
        {
            $upload_dir = $this->_getUploadPath();
            if (!is_dir($upload_dir)) 
            {
                mkdir($upload_dir, $this->_options['mkdir_mode'], true);
            }
            
            $file_path = $this->_getUploadPath($file->name);
            $append_file = $content_range && is_file($file_path) &&
                $file->size > $this->_getFileSize($file_path);
            
            if ($uploaded_file && is_uploaded_file($uploaded_file)) 
            {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) 
                {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } 
                else 
                {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } 
            else 
            {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            
            $file_size = $this->_getFileSize($file_path, $append_file);
            if ($file_size === $file->size)
            {
                $file->url = $this->_getDownloadUrl($file->name);
                foreach($this->_options['image_versions'] as $version => $options)
                {
                    if ($this->_createScaledImage($file->name, $version, $options))
                    {
                        if (!empty($version))
                        {
                            $file->{$version.'_url'} = $this->_getDownloadUrl(
                                $file->name,
                                $version
                            );
                        }
                        else
                        {
                            $file_size = $this->_getFileSize($file_path, true);
                        }
                    }
                }
            }
            else if (!$content_range && $this->_options['discard_aborted_uploads'])
            {
                unlink($file_path);
                $file->error = 'abort';
            }

            $file->size = $file_size;
            $this->_setFileDeleteProperties($file);
        }

        return $file;
    }

    protected function body($str) {
        echo $str;
    }

    protected function _generateResponse($content, $paramName = null)
    {
        $json = json_encode($content);
        $redirect = isset($_REQUEST['redirect']) ?
            stripslashes($_REQUEST['redirect']) : null;
        if (isset($redirect))
        {
            header('Location: '.sprintf($redirect, rawurlencode($json)));
            return null;
        }

        $this->head();

        if (isset($_SERVER['HTTP_CONTENT_RANGE']) && isset($paramName))
        {
            $files = isset($content[$paramName]) ?
                $content[$paramName] : null;

            if ($files && is_array($files) && is_object($files[0]) && $files[0]->size)
            {
                header('Range: 0-'.(fix_integer_overflow(intval($files[0]->size)) - 1));
            }
        }

        _DEBUG($json, 'json');

        $this->body($json);
    }

    protected function _getVersionParam() {
        return isset($_GET['version']) ? basename(stripslashes($_GET['version'])) : null;
    }

    protected function _getFileNameParam() {
        return isset($_GET['file']) ? basename(stripslashes($_GET['file'])) : null;
    }

    protected function _getFileType($file_path)
    {
        switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)))
        {
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return '';
        }
    }

    protected function download() {
        if (!$this->_options['download_via_php'])
        {
            header('HTTP/1.1 403 Forbidden');
            return;
        }

        $file_name = $this->_getFileNameParam();
        if ($this->is_valid_file_object($file_name))
        {
            $file_path = $this->_getUploadPath($file_name, $this->_getVersionParam());
            if (is_file($file_path))
            {
                if (!preg_match($this->_options['inline_file_types'], $file_name)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="'.$file_name.'"');
                    header('Content-Transfer-Encoding: binary');
                } else {
                    // Prevent Internet Explorer from MIME-sniffing the content-type:
                    header('X-Content-Type-Options: nosniff');
                    header('Content-Type: '.$this->_getFileType($file_path));
                    header('Content-Disposition: inline; filename="'.$file_name.'"');
                }
                header('Content-Length: '.$this->_getFileSize($file_path));
                header('Last-Modified: '.gmdate('D, d M Y H:i:s T', filemtime($file_path)));
                readfile($file_path);
            }
        }
    }

    protected function _sendContentTypeHeader()
    {
        header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false))
        {
            header('Content-type: application/json');
        }
        else
        {
            header('Content-type: text/plain');
        }
    }

    protected function _sendAccessControlHeaders()
    {
        header('Access-Control-Allow-Origin: '.$this->_options['access_control_allow_origin']);
        header('Access-Control-Allow-Credentials: '
            .($this->_options['access_control_allow_credentials'] ? 'true' : 'false'));
        header('Access-Control-Allow-Methods: '
            .implode(', ', $this->_options['access_control_allow_methods']));
        header('Access-Control-Allow-Headers: '
            .implode(', ', $this->_options['access_control_allow_headers']));
    }

    public function head()
    {
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Disposition: inline; filename="files.json"');
        // Prevent Internet Explorer from MIME-sniffing the content-type:
        header('X-Content-Type-Options: nosniff');
        if ($this->_options['access_control_allow_origin']) {
            $this->_sendAccessControlHeaders();
        }
        $this->_sendContentTypeHeader();
    }

    public function get()
    {
        $this->download();
    }

    public function post()
    {
        if ($this->_app->request()->isDelete())
        {
            $this->delete();
            return;
        }

        $paramName = null;
        foreach ($_FILES as $key => $value)
        {
            if (isset($value) && (array_key_exists('size', $value) || array_key_exists('error', $value)))
            {
                $paramName = $key;
                break;
            }
        }

        if (!isset($paramName))
        {
            header('HTTP/1.1 400 ' . _T('400', 'error'));
            return;
        }

        $upload = $_FILES[$paramName];

        // Parse the Content-Disposition header, if available:
        $file_name = isset($_SERVER['HTTP_CONTENT_DISPOSITION']) ?
            rawurldecode(preg_replace(
                '/(^[^"]+")|("$)/',
                '',
                $_SERVER['HTTP_CONTENT_DISPOSITION']
            )) : null;

        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range = isset($_SERVER['HTTP_CONTENT_RANGE']) ?
            preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']) : null;
        $size =  $content_range ? $content_range[3] : null;

        $files = array();
        if (isset($upload) && is_array($upload['tmp_name']))
        {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value)
            {
                $files[] = $this->_handleFileUpload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    $size ? $size : $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
            }
        }
        else
        {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $files[] = $this->_handleFileUpload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ? $file_name : (isset($upload['name']) ?
                        $upload['name'] : null),
                $size ? $size : (isset($upload['size']) ?
                        $upload['size'] : $_SERVER['CONTENT_LENGTH']),
                isset($upload['type']) ?
                        $upload['type'] : $_SERVER['CONTENT_TYPE'],
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
        }

        $this->_generateResponse($files, $paramName);
    }

    public function delete()
    {
        $file_name = $this->_getFileNameParam();
        $file_path = $this->_getUploadPath($file_name);
        $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
        if ($success)
        {
            foreach($this->_options['image_versions'] as $version => $options)
            {
                if (!empty($version))
                {
                    $file = $this->_getUploadPath($file_name, $version);
                    if (is_file($file))
                    {
                        unlink($file);
                    }
                }
            }
        }

        $this->_generateResponse(array('success' => $success));
    }

}
