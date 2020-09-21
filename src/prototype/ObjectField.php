<?php

namespace miladm\prototype;

/*
    database field type Object to merge table with other table
*/

class ObjectField extends DatabaseField
{
    public $schemaName;
    public $namespace = null;
    public $leftjoinOn;
}
