<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Lance\Convention;
use Bluefin\Data\Type;

class LogicalDeletion extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_DELETED = '_is_deleted';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_LOGICAL_DELETION, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $fieldModifier = array();
        $fieldModifier[] = Type::TYPE_BOOL;
        $fieldModifier[] = Convention::MODIFIER_TYPE_COMMENT . Convention::buildDisplayName($this->_entity->getSchema()->getLocale(), self::FIELD_NAME_DELETED, usw_to_pascal(self::FIELD_NAME_DELETED));

        $this->_fields[] = self::FIELD_NAME_DELETED;

        $field = $this->_entity->addField(self::FIELD_NAME_DELETED, merge_modifiers($fieldModifier), false, true);
        $field->setRequired();
        $field->setOwnerFeature($this->getFeatureName());
        $field->setInitialValue(0);
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
    }

    public function apply2Pass()
    {

    }
}
