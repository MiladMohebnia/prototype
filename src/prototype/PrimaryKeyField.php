<?php

namespace miladm\prototype;

/*
    database field type fpr primary key of current schema
    currently we are using the int key with auto increment system
*/

class PrimaryKeyField extends DatabaseField
{
    function __construct($name)
    {
        $this->type = "int";
        $this->name = $name;
        $this->length = 11;
        $this->noNull = true;
    }
}
