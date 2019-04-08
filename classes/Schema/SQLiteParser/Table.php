<?php

namespace Schema\SQLiteParser;

class Table
{
    public $columns = [];

    public $temporary;

    public $ifNotExists = false;

    public $tableName;

    public $constraints;
}
