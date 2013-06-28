<?php

require_once '../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use WBT\Business\AuthBusiness;

$app = App::getInstance();

$category = $app->request()->getQueryParam('cat');
if (!isset($category))
{
    header('HTTP/1.1 400 ' . _T('400', 'error'));
    exit();
}

$catCfg = _C("config.upload.{$category}");
if (empty($catCfg))
{
    header('HTTP/1.1 400 ' . _T('400', 'error'));
    exit();
}

$allRoles = array_try_get($catCfg, 'roles');
$options = array_try_get($catCfg, 'options');

if (isset($allRoles))
{
    $authorized = false;

    foreach ($allRoles as $auth => $roles)
    {
        $currentRoles = $app->role($auth)->get();
        $overlappedRoles = array_intersect($roles, $currentRoles);
        if (!empty($overlappedRoles))
        {
            $authorized = true;
            break;
        }
    }

    if (!$authorized)
    {
        header('HTTP/1.1 401 ' . _T('401', 'error'));
        exit();
    }
}

isset($options['custom_dir']) && ($options['custom_dir'] = \Bluefin\VarText::parseVarText($options['custom_dir']));
isset($options['max_file_size']) && ($options['max_file_size'] = parse_size($options['max_file_size']));

$uploadHandler = new \Bluefin\Util\UploadHandler($options);