<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Gateway;
use Bluefin\Lance\Site;

class SiteTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        $siteName = $params;

        $filename = LANCE . "/site/{$siteName}.yml";

        if (!file_exists($filename))
        {
            throw new \Bluefin\Exception\FileNotFoundException($filename);
        }

        $yml = App::loadYmlFileEx($filename);
        $siteConfig = array_try_get($yml, $siteName);

        if (empty($siteConfig))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Invalid site name or config! Site: {$siteName}");
        }

        $site = new Site($siteName, $siteConfig);

        $sitemap = $site->getSitemap();

        $report = array();
        $data = array();

        foreach ($sitemap as $controller => $info)
        {
            list($modulePath, $controllerName) = Gateway::splitDispatchTarget($controller);

            $namespace = "{$siteName}\\Controller" . (!empty($modulePath) ? "\\$modulePath" : '');
            $viewPath = "/{$siteName}" . (!empty($modulePath) ? "/$modulePath" : '');
            $data['namespace'] = $namespace;
            $data['name'] = $controllerName;
            $data['actions'] = $info;

            $filename = normalize_dir_separator('app/lib/' . $namespace . "/{$controllerName}Controller.php");

            $this->_renderTemplate(
                "project/controller_class.twig",
                $filename,
                $data,
                $report
            );

            foreach ($info as $actionName => $actionInfo)
            {
                $filename = normalize_dir_separator('app/view' . $viewPath . "/{$controllerName}.{$actionName}.html");

                $this->_renderTemplate(
                    "project/view_page.twig",
                    $filename,
                    $actionInfo,
                    $report
                );
            }
        }

        $this->_logReport($report);

        //TODO: use param to replace hardcoded www:www
        exec_shell_command("chown -hR www:www " . ROOT);
    }

}
