<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class UpdateTimestamp extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_UPDATED_AT = '_updated_at';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_UPDATE_TIMESTAMP, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $fieldModifier = array();
        $fieldModifier[] = Type::TYPE_TIMESTAMP;
        $fieldModifier[] = Convention::MODIFIER_TYPE_COMMENT . Convention::buildDisplayName($this->_entity->getSchema()->getLocale(), self::FIELD_NAME_UPDATED_AT, usw_to_pascal(self::FIELD_NAME_UPDATED_AT));

        $this->_fields[] = self::FIELD_NAME_UPDATED_AT;
        $field = $this->_entity->addField(self::FIELD_NAME_UPDATED_AT, merge_modifiers($fieldModifier), false, true);
        $field->setRequired();
        $field->setDbAutoInsert();
        $field->setOwnerFeature($this->getFeatureName());
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
    }

    public function apply2Pass()
    {

    }
}
