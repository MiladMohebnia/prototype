<?php

namespace miladm;

use miladm\prototype\ModelHandler;
use miladm\prototype\Schema;
use miladm\prototype\SchemaTable;
use miladm\table\Connection;

abstract class Prototype
{

    private $model = null;

    function __construct()
    {
        $this->model = new ModelHandler();
        $this->init();
        $this->model->setConnection($this->connection());
        $this->model->setupTable();
    }

    abstract function init(): Schema;

    abstract function connection(): Connection;

    public static function model(): ModelHandler
    {
        return (new static())->model;
    }

    public function schema($name = false): Schema
    {
        return $this->model->schema($name);
    }

    public static function create()
    {
        return self::model()->create();
    }

    public static function get(array $condition = [], $limit = false, $start = false, $asc = true)
    {
        return self::model()->get($condition, $limit, $start, $asc);
    }

    public function table(): SchemaTable
    {
        return $this->model->table();
    }
}
