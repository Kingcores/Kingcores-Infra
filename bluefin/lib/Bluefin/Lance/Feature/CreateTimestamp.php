<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class CreateTimestamp extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_CREATED_AT = '_created_at';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_CREATE_TIMESTAMP, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $fieldModifier = array();
        $fieldModifier[] = Type::TYPE_TIMESTAMP;
        $fieldModifier[] = Convention::MODIFIER_TYPE_COMMENT . Convention::buildDisplayName($this->_entity->getSchema()->getLocale(), self::FIELD_NAME_CREATED_AT, usw_to_pascal(self::FIELD_NAME_CREATED_AT));

        $this->_fields[] = self::FIELD_NAME_CREATED_AT;

        $field = $this->_entity->addField(self::FIELD_NAME_CREATED_AT, merge_modifiers($fieldModifier), false, true);
        $field->setRequired();
        $field->setInitialValue(Convention::KEYWORD_DB_EXPR_PREFIX . "NOW()");
        $field->setOwnerFeature($this->getFeatureName());
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
    }

    public function apply2Pass()
    {

    }
}
