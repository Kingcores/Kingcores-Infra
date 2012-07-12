<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Lance\Convention;
use Bluefin\Data\Type;

class LogicalDeletion extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_DELETED = 'is_deleted';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_LOGICAL_DELETION, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $field = $this->_entity->addField(self::FIELD_NAME_DELETED, Type::TYPE_BOOL, false, true);
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
