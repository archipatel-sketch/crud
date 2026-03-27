<?php

namespace ArchipatelSketch\Crud\Exceptions;

use Exception;

class TableNotFoundException extends Exception
{
    protected $table;

    public function __construct($table)
    {
        $this->table = $table;
        parent::__construct("Table '{$table}' not found in your database");
    }

}
