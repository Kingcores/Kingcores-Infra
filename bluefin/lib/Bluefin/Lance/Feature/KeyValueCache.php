<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Exception\GrammarException;

class KeyValueCache extends AbstractFeature implements FeatureInterface
{
    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_KEY_VALUE_CACHE, $entity, $modifiers);
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
                    throw new GrammarException("Unknown creator_field modifier: {$modifier}");
            }
        }

        if (!isset($fieldName))
        {
            throw new GrammarException("A field name is expected for creator_field feature.");
        }

        $field = $this->_entity->getField($fieldName);
        if (!isset($field))
        {
            throw new GrammarException("Invalid field name '{$fieldName}'.");
        }

        $this->_fields[] = $fieldName;
    }
}
