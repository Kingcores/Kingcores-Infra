<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class CreateTimestamp extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_CREATED_AT = 'created_at';
    const FUNCTION_CURRENT_TIMESTAMP = 'db!NOW()';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_CREATE_TIMESTAMP, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $field = $this->_entity->addField(self::FIELD_NAME_CREATED_AT, Type::TYPE_TIMESTAMP, false, true);
        $field->setRequired();
        $field->setInitialValue(self::FUNCTION_CURRENT_TIMESTAMP);
        $field->setOwnerFeature($this->getFeatureName());
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
    }

    public function apply2Pass()
    {

    }
}
