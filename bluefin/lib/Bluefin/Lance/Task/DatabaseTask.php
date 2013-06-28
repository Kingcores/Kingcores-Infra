<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Lance\Convention;
use Bluefin\Lance\ReportEntry;
use Bluefin\Lance\Arsenal;
use Bluefin\Lance\Entity;
use Bluefin\Lance\Schema;

class DatabaseTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        $paramsArray = App::loadYmlFileEx($params);

        if (!array_key_exists('lance', $paramsArray))
        {
            throw new \InvalidArgumentException("'lance' should be given in 'params'.");
        }

        $schema = Arsenal::getInstance()->loadSchema($paramsArray['lance']);
        $schema->loadEntities();

        $data = array('schema' => $schema);
        $report = array();

        $dbETCFile = "db/{$schema->getSchemaName()}." . ENV . ".yml";

        //database runtime config
        Arsenal::getInstance()->log()->info("Writing database config ...", Convention::LOG_CAT_LANCE_CORE);

        $this->_renderTemplate(
            "db/{$schema->getDbType()}/runtime_config.twig",
            "app/etc/{$dbETCFile}",
            $data,
            $report
        );

        $this->_logReport($report);

        //database create script
        Arsenal::getInstance()->log()->info("Writing database create script ...", Convention::LOG_CAT_LANCE_CORE);

        $this->_renderTemplate(
            "db/{$schema->getDbType()}/create_database.twig",
            "app/schema/create_{$schema->getSchemaName()}.sql",
            $data,
            $report
        );

        $this->_logReport($report);

        //truncate tables script
        Arsenal::getInstance()->log()->info("Writing truncate tables script ...", Convention::LOG_CAT_LANCE_CORE);

        $this->_renderTemplate(
            "db/{$schema->getDbType()}/truncate_tables.twig",
            "app/schema/truncate_{$schema->getSchemaName()}.sql",
            $data,
            $report
        );

        $this->_deleteFiles(ROOT . "/app/schema/*.lst", $report);

        $this->_logReport($report);

        Arsenal::getInstance()->log()->info("Writing db rebuild script ...", Convention::LOG_CAT_LANCE_CORE);

        $this->_renderTemplate(
            "db/list_db_rebuild.twig",
            "app/schema/rebuild_{$schema->getSchemaName()}.lst",
            $data,
            $report
        );

        $this->_deleteFiles(ROOT . "/app/schema/{$schema->getSchemaName()}/*.sql", $report);

        $this->_logReport($report);

        //tables create script and relationships
        Arsenal::getInstance()->log()->info("Writing table create scripts ...", Convention::LOG_CAT_LANCE_CORE);

        foreach ($schema->getModelEntities() as $entityName => $entity)
        {
            /**
             * @var \Bluefin\Lance\Entity $entity
             */

            $data['entity'] = $entity;

            $this->_renderTemplate(
                "db/{$schema->getDbType()}/table.twig",
                "app/schema/{$schema->getSchemaName()}/{$entityName}.sql",
                $data,
                $report
            );

            $fKeys = $entity->getForeignKeys();
            if (!empty($fKeys))
            {
                $this->_renderTemplate(
                    "db/{$schema->getDbType()}/relation.twig",
                    "app/schema/{$schema->getSchemaName()}/{$entityName}_relations.sql",
                    $data,
                    $report
                );
            }

            $this->_logReport($report);
        }

        unset($data['entity']);

        $this->prepareDataScript($schema);

        $globalETCFile = APP_ETC . '/global.yml';

        ob_start();
        include($globalETCFile);
        $globalETC = ob_get_clean();

        $globalETCChanged = false;

        if (false === mb_strpos($globalETC, $dbETCFile))
        {
            if (false === mb_strpos($globalETC, 'db/placeholder.yml'))
            {
                throw new \Bluefin\Lance\Exception\GrammarException("The placeholder for db config is expected!");
            }

            $globalETC = str_replace('db/placeholder.yml', $dbETCFile, $globalETC, $count);
            if ($count != 1)
            {
                throw new \Bluefin\Lance\Exception\GrammarException("More than one 'placeholder' for db config is found!");
            }

            $globalETCChanged = true;
        }

        //db auth config
        Arsenal::getInstance()->log()->info("Writing db auth config ...", Convention::LOG_CAT_LANCE_CORE);

        $auth = array();

        foreach ($schema->getAuth() as $authConfig)
        {
            $name = array_try_get($authConfig, 'name', null, true);
            if (is_null($name))
            {
                throw new \Bluefin\Lance\Exception\GrammarException("'name' is required in the auth config section.");
            }

            $auth[$name] = array('class' => '\Bluefin\Auth\DbAuth', 'config' => $authConfig);
        }

        if (!empty($auth))
        {
            $authETC = \Symfony\Component\Yaml\Yaml::dump($auth, 4);
            $authETCFile = "auth/{$schema->getSchemaName()}." . ENV . ".yml";
            file_put_contents(APP_ETC . "/{$authETCFile}", $authETC, LOCK_EX);
            $report[] = new ReportEntry(ReportEntry::OP_UPDATE_FILE, "app/etc/{$authETCFile}");

            if (false === mb_strpos($globalETC, $authETCFile))
            {
                if (false === mb_strpos($globalETC, 'auth/placeholder.yml'))
                {
                    throw new \Bluefin\Lance\Exception\GrammarException("The placeholder for auth config is expected!");
                }

                $globalETC = str_replace('auth/placeholder.yml', $authETCFile, $globalETC, $count);
                if ($count != 1)
                {
                    throw new \Bluefin\Lance\Exception\GrammarException("More than one 'placeholder' for auth config is found!");
                }

                $globalETCChanged = true;
            }
        }

        if ($globalETCChanged)
        {
            file_put_contents($globalETCFile, $globalETC, LOCK_EX);
            $report[] = new ReportEntry(ReportEntry::OP_UPDATE_FILE, $globalETCFile);
        }

        $this->_logReport($report);

        //TODO: use param
        exec_shell_command("chown -hR www:www " . ROOT);
    }

    public function prepareDataScript(Schema $schema)
    {
        $this->_deleteDirIfExist(APP . "/data/{$schema->getSchemaName()}");

        //db data script
        Arsenal::getInstance()->log()->info("Writing data action scripts ...", Convention::LOG_CAT_LANCE_CORE);

        $data = [];
        $report = [];

        $dataFiles = $schema->getData();
        foreach ($dataFiles as $action => $actionFiles)
        {
            $data['action'] = $action;
            $files = array();

            foreach ($actionFiles as $fileEntry)
            {
                $ext = mb_strtolower(pathinfo($fileEntry, PATHINFO_EXTENSION));
                if (!in_array($ext, array('php', 'yml', 'sql', 'bsd')))
                {
                    throw new \Bluefin\Lance\Exception\GrammarException("Invalid data file type: {$ext}! File: {$fileEntry}");
                }

                $file = LANCE . "/data/{$fileEntry}";
                file_exists($file) || ($file = LANCE_BUILTIN . "/data/{$fileEntry}");

                if (!file_exists($file))
                {
                    throw new \Bluefin\Exception\FileNotFoundException($fileEntry);
                }

                $relFile = "/data/{$schema->getSchemaName()}/{$fileEntry}";

                $this->_copyFileIfNotExist($file, APP . $relFile, $report);

                $files[] = "..{$relFile}";
            }

            $data['files'] = $files;

            $this->_renderTemplate(
                "db/list_db_action.twig",
                "app/schema/{$action}_{$schema->getSchemaName()}.lst",
                $data,
                $report
            );

            $this->_logReport($report);
        }
    }
}
