<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class ScheduledTask extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_TASK_ID = '_task_id';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_HAS_SCHEDULED_TASK, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $fieldModifier = array();
        $fieldModifier[] = Type::TYPE_INT;
        $fieldModifier[] = Convention::MODIFIER_TYPE_COMMENT . Convention::buildDisplayName($this->_entity->getSchema()->getLocale(), self::FIELD_NAME_TASK_ID, usw_to_pascal(self::FIELD_NAME_TASK_ID));

        $this->_fields[] = self::FIELD_NAME_TASK_ID;

        $field = $this->_entity->addField(self::FIELD_NAME_TASK_ID, merge_modifiers($fieldModifier), false, true);
        $field->setRequired(false);
        $field->setOwnerFeature($this->getFeatureName());
        $field->setIsCalcSum();
    }

    public function apply2Pass()
    {

    }
}
