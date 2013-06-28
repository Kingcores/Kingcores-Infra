<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Exception\GrammarException;

class OwnerField extends AbstractFeature implements FeatureInterface
{
    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_OWNER_FIELD, $entity, $modifiers);
    }

    public function apply1Pass()
    {
    }

    public function apply2Pass()
    {
        $fieldName = null;

        foreach ($this->_modifiers as $modifier)
        {
            switch ($modifier[0])
            {
                case Convention::MODIFIER_TYPE_DEFAULT:
                    $fieldName = mb_substr($modifier, 1);
                    break;

                default:
                    throw new GrammarException("Unknown owner_field modifier: {$modifier}");
            }
        }

        if (!isset($fieldName))
        {
            throw new GrammarException("A field name is expected for owner_field feature.");
        }

        $this->_fields[] = $fieldName;
        $this->_entity->setOwnerField($fieldName);
    }
}
