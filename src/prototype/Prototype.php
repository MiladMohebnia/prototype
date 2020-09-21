<?php

namespace miladm\prototype;


class Prototype
{
    private $table;
    private $schema = false;


    public function create()
    {
        // return $this->table->query($this->schema()->init_query());
    }

    public function schema($name = false): Schema
    {
        if (!$name && !$this->schema) {
            return trigger_error("you must set the schema name first time you are setting it.");
        }
        if (!$this->schema) {
            $this->schema = new Schema($name);
        }
        return $this->schema;
    }
}
