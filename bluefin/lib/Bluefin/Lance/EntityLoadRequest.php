<?php

namespace Bluefin\Lance;

use Bluefin\Lance\Convention;

class EntityLoadRequest
{
    public $callerEntity;
    public $requestedEntityFullName;
    public $state;
    public $requiredEntityStatus;

    public function __construct(Entity $callerEntity,
                                $requestedEntityFullName,
                                $state,
                                $requiredEntityStatus)
    {
        $this->callerEntity = $callerEntity;
        $this->requestedEntityFullName = $requestedEntityFullName;
        $this->state = $state;
        $this->requiredEntityStatus = $requiredEntityStatus;
    }
}
