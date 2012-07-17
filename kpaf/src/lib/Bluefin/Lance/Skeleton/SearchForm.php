<?php

namespace Bluefin\Lance\Skeleton;

use Bluefin\Lance\SchemaSet;
 
class SearchForm extends AbstractBlock implements BlockInterface
{
    public function __construct(SchemaSet $schemaSet, $controllerName, $actionName, array $config)
    {
        parent::__construct($schemaSet, $controllerName, $actionName, $config);
    }

    public function renderAction()
    {

    }

    public function renderView()
    {

    }
}
