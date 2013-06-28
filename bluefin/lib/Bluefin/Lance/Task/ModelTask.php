<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Arsenal;
use Bluefin\Lance\Entity;

class ModelTask extends TaskBase implements TaskInterface
{
    public function execute($params)
    {
        $schema = Arsenal::getInstance()->loadSchema($params);
        $schema->loadEntities();

        $data = array('schema' => $schema);
        $report = array();

        $this->_renderTemplate(
            "db/database_class.twig",
            "app/lib/{$schema->getNamespace()}/Model/{$schema->getSchemaNamePascal()}Database.php",
            $data,
            $report
        );

        $this->_deleteFiles(ROOT . "/lib/{$schema->getNamespace()}/Model/*.php", $report);

        $this->_logReport($report);

        foreach ($schema->getModelEntities() as $entity)
        {
            /**
             * @var \Bluefin\Lance\Entity $entity
             */

            $data['entity'] = $entity;

            $stateActions = $entity->getStateActions();

            if (!empty($stateActions))
            {
                foreach ($stateActions as $action => &$froms)
                {
                    foreach ($froms as $from)
                    {
                        $target = $entity->getFST()[$from][Convention::KEYWORD_ENTITY_FSM_TRANSITIONS][$action][Convention::KEYWORD_ENTITY_FSM_TARGET];

                        if ($target == $from)
                        {
                            array_erase($froms, $from);
                        }
                    }

                    if (empty($froms))
                    {
                        unset($stateActions[$action]);
                    }
                }

                $data['actions'] = $stateActions;
            }
            else
            {
                unset($data['actions']);
            }

            $this->_renderTemplate(
                "db/model_class.twig",
                "app/lib/{$schema->getNamespace()}/Model/{$schema->getSchemaNamePascal()}/{$entity->getCodeNamePascal()}.php",
                $data,
                $report
            );

            $this->_logReport($report);

            if (!is_null($entity->getService()) || $entity->hasActionApi())
            {
                $this->_renderTemplate(
                    "service/api.twig",
                    "app/lib/{$schema->getNamespace()}/API/{$schema->getSchemaNamePascal()}/{$entity->getCodeNamePascal()}API.php",
                    $data,
                    $report
                );

                $this->_logReport($report);
            }
        }

        foreach ($schema->getCodeEntities() as $entity)
        {
            /**
             * @var \Bluefin\Lance\Entity $entity
             */
            $data['entity'] = $entity;

            $this->_renderTemplate(
                "db/{$entity->getEntityType()}_class.twig",
                "app/lib/{$schema->getNamespace()}/Model/{$schema->getSchemaNamePascal()}/{$entity->getCodeNamePascal()}.php",
                $data,
                $report
            );

            $this->_logReport($report);
        }

        //TODO: use param
        //exec_shell_command("chown -hR www:www " . ROOT);
    }
}
