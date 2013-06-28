<?php

require_once "phing/Task.php";
require_once '../lib/Bluefin/bluefin.php';

use Bluefin\App;
use Bluefin\Lance\FileRenderer;
use Bluefin\Lance\Arsenal;

class RockTask extends Task
{
    protected $_dbServers;

    /**
     * The main entry point method.
     */
    public function main()
    {
        $projectFile = '../project.yml';

        $projectConfig = App::loadYmlFileEx($projectFile);
        $apps = array_try_get($projectConfig, 'app', []);
        $dbServers = array_try_get($projectConfig, 'db_server', []);

        foreach ($dbServers as &$server)
        {
            $this->_setDbServerDefaultSettings($server);
        }
        $this->_dbServers = $dbServers;

        foreach ($apps as $appName => $appconfig)
        {
            $this->_processApp($appName, $appconfig);
        }
    }

    protected function _processApp($appName, array $appConfig)
    {
        $namespace = usw_to_pascal($appName);
        $dbs = array_try_get($appConfig, 'db');

        if (isset($dbs))
        {
            foreach ($dbs as $dbName => $dbServer)
            {
                $server = array_try_get($this->_dbServers, $dbServer);
                App::assert(isset($server));

                $dbConfigFile = "../lance/{$dbName}.yml";
                if (file_exists($dbConfigFile))
                {

                }
                else
                {
                    FileRenderer::render(
                        'project/db.yml.twig',
                        "lance/{$dbName}.yml",
                        array_merge([ 'dbName' => $dbName, 'locale' => Arsenal::getInstance()->locale() ], $server)
                    );
                }
            }
        }
    }

    protected function _setDbServerDefaultSettings(array &$server)
    {
        $type = array_try_get($server, 'type');
        App::assert(isset($type));

        $defaults = Arsenal::loadLanceFile("etc/db/{$type}.yml");

        $server = array_merge($defaults, $server);
    }
}