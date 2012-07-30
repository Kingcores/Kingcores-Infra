<?php

namespace Bluefin\Lance;

class ReportEntry
{
    const OP_CREATE_DIR = 'CREATE_DIR';
    const OP_DELETE_DIR = 'DELETE_DIR';

    const OP_CREATE_FILE = 'CREATE_FILE';
    const OP_DELETE_FILE = 'DELETE_FILE';
    const OP_UPDATE_FILE = 'UPDATE_FILE';

    public $op;
    public $target;
    public $succeeded;

    public function __construct($op, $target, $succeeded = true)
    {
        $this->op = $op;
        $this->target = $target;
        $this->succeeded = $succeeded;
    }
}
