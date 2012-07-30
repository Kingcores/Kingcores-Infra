<?php

namespace Bluefin\Lance\Creator;

use Bluefin\App;
use Bluefin\View;
use Bluefin\Lance\Convention;
use Bluefin\Lance\SchemaSet;
use Bluefin\Lance\FileRenderer;
use Bluefin\Lance\ReportEntry;

class CreatorBase
{
    protected function _createDirIfNotExist($dir, array &$report = null)
    {
        ensure_dir_exist($dir) && isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_CREATE_DIR, $dir));
    }

    protected function _deleteDirIfExist($dir, array &$report = null)
    {
        del_dir($dir) && isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_DELETE_DIR, $dir));
    }

    protected function _copyFileIfNotExist($target, $source, array &$report = null)
    {
        ensure_file_exist($target, $source) &&
            isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_CREATE_FILE, $target));
    }

    protected function _deleteFileIfExist($file, array &$report = null)
    {
        file_exists($file) && @unlink($file)
            && isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_DELETE_FILE, $file));
    }

    protected function _deleteFiles($filePattern, array &$report = null)
    {
        $result = del_files($filePattern);

        if (!empty($result) && isset($report))
        {
            foreach ($result as $file)
            {
                $report[] = new ReportEntry(ReportEntry::OP_DELETE_FILE, $file);
            }
        }
    }

    protected function _renderTemplate($template, $target, array $data, array &$report = null)
    {
        if (del_file($target))
        {
            isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_DELETE_FILE, $target));
        }

        FileRenderer::render(
            $template,
            $target,
            $data
        );

        isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_CREATE_FILE, $target));
    }
}
