<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class UpdateTimestamp extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_UPDATED_AT = 'updated_at';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_UPDATE_TIMESTAMP, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $field = $this->_entity->addField(self::FIELD_NAME_UPDATED_AT, Type::TYPE_TIMESTAMP, false, true);
        $field->setRequired();
        $field->setInitialValue(0);
        $field->setOwnerFeature($this->getFeatureName());
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
    }

    public function apply2Pass()
    {

    }
}
