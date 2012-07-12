<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;

class UpdatedBy extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_UPDATED_BY = 'updated_by';

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_UPDATED_BY, $entity, $modifiers);
    }

    public function apply1Pass()
    {
    }

    public function apply2Pass()
    {
        $config = $this->_entity->getSchemaSet()->getFeatureConfig($this->getFeatureName());
        $reference = $config['reference'];
        $source = $config['source'];
        $fallback = $config['fallback_value'];

        $functor = "f!context({$source}, {$fallback})";

        $field = $this->_entity->addField(self::FIELD_NAME_UPDATED_BY, $reference, false, true);
        $field->setRequired();
        $field->setInitialValue($functor);
        $field->setUpdateValue($functor);
        $field->setOwnerFeature($this->getFeatureName());
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
    }
}
