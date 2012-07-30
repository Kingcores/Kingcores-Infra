<?php

namespace Bluefin\Lance;

use Bluefin\App;

class YAMLRunner
{
    private $_schemaSet;

    public function __construct(SchemaSet $schemaSet)
    {
        $this->_schemaSet = $schemaSet;
    }

    public function run($yamlFile)
    {
        $content = App::loadYmlFileEx($yamlFile);

        foreach ($content as &$modelData)
        {
            $entityName = $modelData['entity'];
            $entity = $this->_schemaSet->getUsedEntity($entityName);
            if (empty($entity))
            {
                trigger_error("Useless entity '$entityName'!");
                continue;
            }

            unset($modelData['entity']);

            foreach ($modelData as $command => $data)
            {
                foreach ($data as $record)
                {
                    $this->_processCommand($entity, $command, $record);
                }
            }
        }
    }

    private function _processCommand(Entity $entity, $command, $record)
    {
        //echo $command . "<br>";
        //var_dump($record) . "<br>";

        $modifiers = split_modifiers($command);
        $command = array_shift($modifiers);

        $byColumn = null;

        if (!empty($modifiers) && mb_substr($modifiers[0], 0, 3) == 'on=')
        {
            $byColumn = mb_substr($modifiers[0], 3);
        }

        /**
         * @var \Bluefin\Data\Model $modelClass
         */
        $modelClass = $entity->getClassFullName();

        /**
         * @var \Bluefin\Data\Model $model
         */
        $model = new $modelClass();
        $delayedRecord = array();

        //var_dump($record);
        //echo "<br>";

        foreach ($record as $key => $value)
        {
            /**
             * @var \Bluefin\Lance\Field $field
             */
            $field = $entity->getField($key);
            if (is_null($field))
            {
                throw new \Bluefin\Lance\Exception\GrammarException("Unknown field. Entity: {$entity->getCodeName()} Field: {$key}");
            }

            if ($field->isManyToManyField() || $field->isOneToManyField())
            {
                is_array($value) || ($value = array($value));

                $relationship = $entity->getM2NRelationship($key);
                $refEntity = $this->_schemaSet->getUsedEntity($relationship['targetEntity']);

                /**
                 * @var \Bluefin\Data\Model $refModelClass
                 */
                $refModelClass = $refEntity->getClassFullName();

                $values = array();

                foreach ($value as $item)
                {
                    //echo "ok1<br>";

                    $value2 = $refModelClass::pkValue($item);

                    if (false === $value2)
                    {
                        $this->_processCommand($refEntity, 'insert', $item);

                        //echo "ok2<br>";

                        $item = $refModelClass::pkValue($item);

                        App::assert(false !== $item);
                    }
                    else
                    {
                        $item = $value2;
                    }

                    $values[] = $item;
                }

                $delayedRecord[$relationship['relationEntity']] = array('relationship' => $relationship, 'values' => $values);

                continue;
            }
            else if ($field->isReferenceField() && is_array($value))
            {
                $refEntityName = $entity->getReferencedEntityName($field->getConfiguredName());
                $refEntity = $this->_schemaSet->getUsedEntity($refEntityName);

                /**
                 * @var \Bluefin\Data\Model $refModelClass
                 */
                $refModelClass = $refEntity->getClassFullName();
                $fieldName = $field->getReferencedFieldName();

                $value2 = $refModelClass::scalar($fieldName, $value);

                if (false === $value2)
                {
                    $this->_processCommand($refEntity, 'insert', $value);

                    $value = $refModelClass::scalar($fieldName, $value);

                    App::assert(false !== $value);
                }
                else
                {
                    $value = $value2;
                }
            }
            else if (is_array($value))
            {
                $fieldName = $field->getFieldName();

                $value2 = $modelClass::scalar($fieldName, $value);

                if (false === $value2)
                {
                    throw new \Bluefin\Exception\ModelException(
                        "Record not found! Entity: " . $entity->getEntityExportFullName() .
                        " Condition: " . Convention::dumpArray($value));
                }
                else
                {
                    $value = $value2;
                }
            }
            else
            {
                $value = PHPCodingLogic::translateValue($field, $value, true);
                //echo bin2hex($value) . "<br/>";
                //echo $field->getFieldName();
            }

            $model->set($field->getFieldName(), $value);
        }

        switch ($command)
        {
            case 'insert':
                $model->insert();
                break;

            case 'update':
                $model->update($byColumn);
                break;

            default:
                throw new \Bluefin\Lance\Exception\GrammarException("Unknown command: " . $command);
        }

        if (!empty($delayedRecord))
        {
            //echo "ok3<br>";

            foreach ($delayedRecord as $relationEntityName => $relationRecord)
            {
                $refEntity = $this->_schemaSet->getUsedEntity($relationEntityName);
                $relationship = $relationRecord['relationship'];

                //var_dump($refEntity->getForeignKeys());// $relationship['localField'];

                $fk1 = $refEntity->getForeignKey($relationship['localField']);
                //var_dump($model->data());
                //echo "{$fk1[2]}<br/>";
                $localField = $fk1[2];

                $localValue = $model->get($localField);

                //var_dump($model->data()); echo "<br>";

                if (!isset($localValue))
                {
                    if (!isset($byColumn) || !isset($model->$byColumn))
                    {
                        throw new \Bluefin\Lance\Exception\GrammarException("Field [{$localValue}] is required.");
                    }

                    //echo "ok4<br>";

                    $model->load(array($byColumn => $model->get($byColumn)));

                    //var_dump($model->data()); echo "<br>";

                    $localValue = $model->get($localField);

                    App::assert(isset($localValue), "Required field [{$localField}] is missing!");
                }

                foreach ($relationRecord['values'] as $one)
                {
                    $tmpRecord = array(
                        $relationship['localField'] => $localValue,
                        $relationship['targetField'] => $one,
                    );

                    //var_dump($tmpRecord); echo "<br>";

                    $this->_processCommand($refEntity, 'insert', $tmpRecord);
                }
            }
        }
    }
}
