<?php

namespace miladm\prototype;

/*
    database main structure and required fields to work on schema
*/

class DatabaseField
{
    public $type;
    public $name;
    public $length = false;
    public $notNull = false;
    public $unique = false;
    public $default = false;
}
