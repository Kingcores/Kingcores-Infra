<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class AutoUUID extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_SUFFIX = '_id';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_AUTO_UUID, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $fieldName = $this->_entity->getCodeName() . self::FIELD_NAME_SUFFIX;

        $fieldModifier = array();
        $fieldModifier[] = Type::TYPE_UUID;
        $fieldModifier[] = Convention::MODIFIER_TYPE_COMMENT . 'UUID';
        $fieldModifier[] = Convention::MODIFIER_TYPE_DEFAULT . Convention::KEYWORD_AUTO_VALUE;

        $this->_fields[] = $fieldName;

        $field = $this->_entity->addField($fieldName, merge_modifiers($fieldModifier), true, true);
        $field->setRequired();
        $field->setReadonlyOnUpdating();
        $field->setOwnerFeature($this->getFeatureName());
        $this->_entity->setPrimaryKey($fieldName);
    }

    public function apply2Pass()
    {
    }
}
