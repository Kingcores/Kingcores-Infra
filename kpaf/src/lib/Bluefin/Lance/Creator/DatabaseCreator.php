<?php

namespace Bluefin\Lance\Creator;

use Bluefin\App;
use Bluefin\View;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Schema;
use Bluefin\Lance\FileRenderer;
use Bluefin\Lance\ReportEntry;
use Bluefin\Lance\Arsenal;
use Bluefin\Lance\Entity;

class DatabaseCreator extends CreatorBase
{
    public function create($params)
    {
        $paramsArray = App::loadYmlFileEx($params);

        if (!array_key_exists('db.name', $paramsArray))
        {
            throw new \InvalidArgumentException("'db.name' should be given in 'params'.");
        }

        $schema = Arsenal::getInstance()->loadSchema($paramsArray['db.name']);
        $schema->loadEntities();

        $data = array('schema' => $schema);
        $report = array();

        $this->_renderTemplate(
            "db/{$schema->getDbType()}/runtime_config.twig",
            "app/etc/db/{$schema->getSchemaName()}." . ENV . ".yml",
            $data,
            $report
        );

        $this->_renderTemplate(
            "db/{$schema->getDbType()}/create_database.twig",
            "app/schema/create_{$schema->getSchemaName()}.sql",
            $data,
            $report
        );

        $this->_renderTemplate(
            "db/{$schema->getDbType()}/drop_tables.twig",
            "app/schema/drop_{$schema->getSchemaName()}.sql",
            $data,
            $report
        );

        $this->_renderTemplate(
            "db/{$schema->getDbType()}/truncate_tables.twig",
            "app/schema/truncate_{$schema->getSchemaName()}.sql",
            $data,
            $report
        );

        $this->_renderTemplate(
            "db/list_rebuild_db.twig",
            "app/schema/rebuild_{$schema->getSchemaName()}.lst",
            $data,
            $report
        );

        $this->_deleteFiles(ROOT . "/app/schema/{$schema->getSchemaName()}/*.sql", $report);

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
        }

        return $report;
    }
}
