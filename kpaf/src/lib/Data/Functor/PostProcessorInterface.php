<?php

namespace Bluefin\Data\Functor;

interface PostProcessorInterface
{
    function process($rawValue, array $dataSet);
}
