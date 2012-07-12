<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;

class ChangeLog extends AbstractFeature implements FeatureInterface
{
    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct($entity, $modifiers);
    }

    public function apply1Pass()
    {
    }

    public function apply2Pass()
    {

    }
}
