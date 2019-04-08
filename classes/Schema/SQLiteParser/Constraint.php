<?php

namespace Schema\SQLiteParser;

class Constraint
{
    public $name;

    public $primaryKey;

    public $unique;

    public $foreignKey;


    /* column */
    public $notNull;
}
