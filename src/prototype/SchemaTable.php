<?php

namespace miladm\prototype;

use miladm\table\Connection;
use miladm\table\Table;

/*
    create a table class from schema to be used by data-access layer
*/

class SchemaTable extends Table
{
    private $_name = null;
    private $_key = null;
    private $_connection = null;

    public function connection()
    {
        return $this->_connection;
    }

    public function tableName()
    {
        return $this->_name;
    }

    public function key()
    {
        return $this->_key;
    }

    function __construct(Schema $schema, Connection $connection)
    {
        $this->_name = $schema->getName();
        $this->_key = $schema->getKey();
        $this->_connection = $connection;
        parent::__construct();
    }
}
