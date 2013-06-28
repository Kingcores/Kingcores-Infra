<?php

namespace Bluefin\Lance;

use Bluefin\App;

class YAMLRunner
{
    private $_schema;

    public function __construct(Schema $schema)
    {
        $this->_schema = $schema;
    }

    public function run($yamlFile)
    {
        $content = App::loadYmlFileEx($yamlFile);

        foreach ($content as $entityName => $data)
        {
            $parts = explode('|', $entityName, 2);
            $entityName = $parts[0];

            $entity = $this->_schema->getLoadedModelEntity($entityName);
            if (empty($entity))
            {
                throw new \Bluefin\Lance\Exception\GrammarException("Unknown entity: {$entityName}");
            }

            foreach ($data as $record)
            {
                $this->_processData($entity, $record);
            }

            Arsenal::getInstance()->log()->info("Imported data into '{$entityName}'.", Convention::LOG_CAT_LANCE_CORE);
        }
    }

    private function _processData(Entity $entity, $record)
    {
        /**
         * @var \Bluefin\Data\Model $modelClass
         */
        $modelClass = $this->_schema->getEntityModelClass($entity->getCodeNamePascal());

        $dataRecord = [];

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

            $fieldName = $field->getFieldName();

            if ($field->isReferenceField() && is_array($value))
            {//外键
                $refEntityName = $entity->getReferencedEntityName($field->getConfiguredName());
                $refEntity = $this->_schema->getLoadedModelEntity($refEntityName);

                /**
                 * @var \Bluefin\Data\Model $refModelClass
                 */
                $refModelClass = $this->_schema->getEntityModelClass($refEntity->getCodeNamePascal());
                $refFieldName = $field->getReferencedFieldName();

                $value2 = $refModelClass::fetchValue($refFieldName, $value);

                if (false === $value2)
                {//引用的记录不存在，先添加一条
                    $this->_processData($refEntity, $value);

                    $value = $refModelClass::fetchValue($refFieldName, $value);

                    App::assert(false !== $value);
                }
                else
                {
                    $value = $value2;
                }
            }
            else if (is_array($value))
            {
                $value = $modelClass::fetchValue($fieldName, $value);
            }
            else
            {
                $value = PHPCodingLogic::evaluateValue($field, $value);
            }

            $dataRecord[$fieldName] = $value;
        }

        try
        {
            /**
             * @var \Bluefin\Data\Model $model
             */
            $model = new $modelClass();
            $model->apply($dataRecord);

            $model->insert(true);
        }
        catch (\Exception $dbe)
        {
            Arsenal::getInstance()->log()->debug($dataRecord, Convention::LOG_CAT_LANCE_DIAG);

            $pe = $dbe->getPrevious();
            if (isset($pe))
            {
                Arsenal::getInstance()->log()->error($pe->getMessage(), Convention::LOG_CAT_LANCE_CORE);
                Arsenal::getInstance()->log()->error($pe->getTraceAsString(), Convention::LOG_CAT_LANCE_CORE);
            }
            else
            {
                Arsenal::getInstance()->log()->error($dbe->getMessage(), Convention::LOG_CAT_LANCE_CORE);
                Arsenal::getInstance()->log()->error($dbe->getTraceAsString(), Convention::LOG_CAT_LANCE_CORE);
            }

            throw $dbe;
        }
    }
}
