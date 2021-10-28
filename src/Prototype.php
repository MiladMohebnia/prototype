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
        $defaultDataObject = $this->defaultDataObject();
        if ($defaultDataObject) {
            if (is_object($defaultDataObject) && $defaultDataObject instanceof DataObject) {
                $defaultDataObject = get_class($defaultDataObject);
            }
            if (class_exists($defaultDataObject)) {
                $this->model->dataObject_set($this->defaultDataObject());
            }
        }
    }

    abstract function init(): Schema;

    abstract function connection();

    public function defaultDataObject()
    {
        return false;
    }

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

    public static function trace(): ModelHandler
    {
        return self::model()->trace();
    }

    public static function hasMany(Prototype $targetPrototype, $mapping): ModelHandler
    {
        $targetTableName = $targetPrototype->model()->table()->tableName();
        $model = self::model();
        $model->schema()->hasMany($targetTableName, $targetPrototype, $mapping);
        $model->hasMany($targetPrototype->model(), $mapping);

        // code below to start mapping!
        // $currentTableName = $model->table()->name();
        // $currentTableKey = $model->schema()->getKey();
        // $model->mapMerge($currentTableName . '.' . $currentTableKey);
        return $model;
    }

    public static function map(array $map): ModelHandler
    {
        return self::model()->map($map);
    }

    public static function add(array $data)
    {
        return self::model()->add($data);
    }

    public static function update(array $newData, array $condition)
    {
        return self::model()->update($newData, $condition);
    }

    public static function get(array $condition = [], $limit = false, $start = false, $asc = true)
    {
        return self::model()->get($condition, $limit, $start, $asc);
    }

    public static function getById(int $id)
    {
        return self::model()->getById($id);
    }

    public static function getOne(array $condition = [], $asc = true)
    {
        return self::model()->getOne($condition, $asc);
    }

    public static function getFirst(array $condition = [])
    {
        return self::model()->getFirst($condition);
    }

    public static function getLast(array $condition = [])
    {
        return self::model()->getLast($condition);
    }

    public static function count(array $condition = [])
    {
        return self::model()->count($condition);
    }

    public static function sum(array $condition, $column)
    {
        return self::model()->sum($condition, $column);
    }

    public static function expect(array $condition = [], $number = 1)
    {
        return self::model()->expect($condition, $number);
    }

    public static function delete(array $condition)
    {
        return self::model()->delete($condition);
    }

    public static function dataObject($className)
    {
        return self::model()->dataObject_set($className);
    }

    public function table(): SchemaTable
    {
        return $this->model->table();
    }

    public static function query(string $query)
    {
        return self::model()->query($query);
    }
}
