<?php
require_once '../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use Upyun\WeibotuiUpyun;

function imgupload ()
{
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $imgFile = $_FILES['imgfile'];
        $imgFileName = $imgFile['name'];
        $imgFileTmpName = $imgFile['tmp_name'];
        $imgFileType = substr($imgFileName, strrpos($imgFile['name'], ".") + 1);
        $imgName = $imgFileTmpName . '.' . $imgFileType;
        move_uploaded_file($imgFileTmpName,$imgName);
    }
    return $imgName;
}

function testUpyun($imgName)
{
    $app = \Bluefin\App::getInstance();

    //$fileName = '../images/logo_80.png';
    $url = WeibotuiUpyun::uploadImage($imgName);
    return $url;
}
$fileName = imgupload();
$url = testUpyun($fileName);
//$showId = $_REQUEST['showid'];

?>

<script type="text/javascript">
    window.parent.stopUpload('<?php echo $url; ?>');
</script>

