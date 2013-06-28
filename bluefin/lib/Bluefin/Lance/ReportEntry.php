<?php

namespace Bluefin\Lance;

class ReportEntry
{
    const OP_CREATE_DIR = 'CREATE DIR';
    const OP_DELETE_DIR = 'DELETE DIR';
    const OP_COPY_DIR = 'COPY DIR';

    const OP_CREATE_FILE = 'CREATE FILE';
    const OP_COPY_FILE = 'COPY FILE';
    const OP_DELETE_FILE = 'DELETE FILE';
    const OP_UPDATE_FILE = 'UPDATE FILE';

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
