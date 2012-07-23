<?php


function add_dir_separator($dir)
{
    $dir = normalize_dir_separator($dir);
    $lc = substr($dir, -1);
    if ($lc != DIRECTORY_SEPARATOR) return $dir . DIRECTORY_SEPARATOR;
    return $dir;
}

function normalize_dir_separator($dir)
{
    if (DIRECTORY_SEPARATOR != '\\') return strtr($dir, '\\', DIRECTORY_SEPARATOR);
    return strtr($dir, '/', DIRECTORY_SEPARATOR);
}

function ensure_dir_exist($dir, $mode = \Bluefin\Common::DIR_MODE_OWNER_WRITE_OTHER_READONLY)
{
    if (!is_dir($dir))
    {
        mkdir($dir, $mode, true) || die("Creating directory \"{$dir}\" failed!");

        return 1;
    }

    return 0;
}

function ensure_file_exist($target, $source = null, $mode = \Bluefin\Common::FILE_MODE_OWNER_WRITE_OTHER_READONLY)
{
    if (!file_exists($target))
    {
        if (isset($source))
        {
            $dir = dirname($target);
            ensure_dir_exist($dir);

            copy($source, $target) || die("Copying file from \"{$source}\" to \"{$target}\" failed!");
            chmod($target, $mode);

            return 1;
        }
        else
        {
            die("File \"{$target}\" does not exist!");
        }
    }

    return 0;
}

function save_var_to_php($filename, $var)
{
    file_put_contents($filename, "<?php return " . var_export($var, true) . ';', LOCK_EX);
}

function copy_dir($source, $dest)
{
    if (!is_dir($source))
    {
        die("Directory \"{$source}\" not exist!");
    }

    if (!is_dir($dest))
    {
        mkdir($dest, null, true);
    }

    $source = add_dir_separator($source);
    $dest = add_dir_separator($dest);

    if (false === ($handle = opendir($source)))
    {
        die("Open directory \"{$source}\" failed!");
    }

    while (false !== ($file = readdir($handle)))
    {
        if ($file == '.' || $file == '..') continue;

        $fullname = $source . $file;
        if (is_dir($fullname))
        {
            copy_dir($fullname, $dest . $file);
        }
        else
        {
            copy($fullname, $dest . $file);
        }
    }

    closedir($handle);
}
 
function del_dir($dir, array &$list = null)
{
    if (!is_dir($dir))
    {
        return;
    }

    if (false === ($handle = opendir($dir)))
    {
        die("Opening directory \"{$dir}\" failed!");
    }

    $dir = add_dir_separator($dir);

    while (false !== ($file = readdir($handle)))
    {
        if ($file == '.' || $file == '..') continue;

        $fullname = $dir . $file;
        if (is_dir($fullname))
        {
            del_dir($fullname);
        }
        else
        {
            unlink($fullname);
            if (isset($list))
            {
                $list[] = $fullname;
            }
        }
    }

    closedir($handle);

    rmdir($dir);
    if (isset($list))
    {
        $list[] = $dir;
    }
}

function del_files($filesPattern)
{
    $result = array();
    
    foreach (glob($filesPattern) as $filename)
    {
        $result[] = $filename;
        unlink($filename);
    }

    return $result;
}

/**
 * @param string $p1
 * @param string $p2
 * @param mixed $_ [optional]
 * @return string
 */
function build_path($p1, $p2, $_ = null)
{
    $path = $p1;

    $args = func_get_args();
    array_shift($args);

    foreach ($args as $p)
    {
        $path = rtrim($path, '/') . str_pad_if($p, '/', true, false);
    }

    return $path;
}