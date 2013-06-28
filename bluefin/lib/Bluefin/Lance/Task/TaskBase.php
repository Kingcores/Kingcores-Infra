<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Lance\Convention;
use Bluefin\Lance\FileRenderer;
use Bluefin\Lance\ReportEntry;

class TaskBase
{
    protected function _createDirIfNotExist($dir, array &$report = null)
    {
        ensure_dir_exist($dir) && isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_CREATE_DIR, $dir));
    }

    protected function _deleteDirIfExist($dir, array &$report = null)
    {
        del_dir($dir) && isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_DELETE_DIR, $dir));
    }

    protected function _copyDir($source, $target, array &$report = null)
    {
        copy_dir($source, $target) &&
            isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_COPY_DIR, $target));
    }

    protected function _copyFileIfNotExist($source, $target, array &$report = null)
    {
        ensure_file_exist($target, $source) &&
            isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_COPY_FILE, $target));
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

    protected function _addPlaceHolder($target, array &$report = null)
    {
        if (del_file($target))
        {
            isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_DELETE_FILE, $target));
        }

        FileRenderer::render(
            'project/placeholder.yml.twig',
            $target,
            array('uid' => uuid_gen())
        );

        isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_CREATE_FILE, $target));
    }

    protected function _appendTemplate($template, $target, array $data, array &$report = null)
    {
        FileRenderer::render(
            $template,
            $target,
            $data,
            true
        );

        isset($report) && ($report[] = new ReportEntry(ReportEntry::OP_UPDATE_FILE, $target));
    }

    protected function _logReport(array &$report)
    {
        while (!empty($report))
        {
            /**
             * @var ReportEntry $entry
             */
            $entry = array_shift($report);

            \Bluefin\Lance\Arsenal::getInstance()->log()->info("{$entry->op}: {$entry->target} " . ($entry->succeeded ? "[OK]\n" : "[FAIL]\n"), 'report');
        }
    }
}
