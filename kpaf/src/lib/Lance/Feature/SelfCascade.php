<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Exception\GrammarException;

class SelfCascade extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_PARENT = 'parent';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_SELF_CASCADE, $entity, $modifiers);
    }

    public function apply1Pass()
    {
    }

    public function apply2Pass()
    {
        $field = $this->_entity->addField(self::FIELD_NAME_PARENT, $this->_entity->getEntityFullName() . '|c="Parent Node"', false, true);
        $field->setRequired(false);
        $field->setOwnerFeature($this->getFeatureName());
    }
}
