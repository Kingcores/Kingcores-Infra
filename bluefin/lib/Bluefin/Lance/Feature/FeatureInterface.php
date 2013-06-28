<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;

interface FeatureInterface
{
    function cloneFeatureTo(Entity $entity);
    function apply1Pass();
    function apply2Pass();
    function getRelatedFields();
}