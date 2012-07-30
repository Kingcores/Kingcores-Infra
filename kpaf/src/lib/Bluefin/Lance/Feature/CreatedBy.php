<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class CreatedBy extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_CREATED_BY = 'created_by';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_CREATED_BY, $entity, $modifiers);
    }

    public function apply1Pass()
    {
    }

    public function apply2Pass()
    {
        $config = $this->_entity->getSchema()->getFeatureConfig($this->getFeatureName());
        $reference = $config['reference'];
        $source = $config['source'];
        $fallback = $config['fallback_value'];

        $field = $this->_entity->addField(self::FIELD_NAME_CREATED_BY, $reference, false, true);
        $field->setRequired();
        $field->setInitialValue("f!context({$source}, {$fallback})");
        $field->setOwnerFeature($this->getFeatureName());
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
    }
}
