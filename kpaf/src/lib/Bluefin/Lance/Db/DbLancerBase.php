<?php

namespace Bluefin\Lance\Db;

use Bluefin\Lance\Schema;

class DbLancerBase
{
    protected $_schema;

    public function __construct(Schema $schema)
    {
        $this->_schema = $schema;
    }
}
