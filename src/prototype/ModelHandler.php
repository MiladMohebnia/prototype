<?php

namespace miladm\prototype;

use miladm\DataObject;
use miladm\table\Connection;

class ModelHandler
{
    /** @var SchemaTable table*/
    private $table = null;
    private $schema = false;
    private $connection = false;
    private $mappingSchema = [];
    private $mapMergingPrototypeName = false;
    private $dataObject = false;


    function __construct()
    {
    }

    public function create()
    {
        foreach ($this->schema()->init_queryList() as $query) {
            $this->table->safeMode()->query($query);
        }
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

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setupTable()
    {
        $this->table = $this->schemaTable_get();
        // left-join all object fields in schema
        foreach ($this->schema()->leftjoinList() as $value) {
            $targetTable = $value->table->coverName($value->coverName);
            $this->table = $this->table->leftjoin($targetTable, $value->mapping);
        }
        return true;
    }

    public function schemaTable_get()
    {
        $table = new SchemaTable($this->schema(), $this->connection);
        $table->fetchArray();
        return $table;
    }

    public function leftJoin($targetTable, $mapping)
    {
        $this->table = $this->table->leftJoin($targetTable, $mapping);
        return $this;
    }

    public function hasMany(ModelHandler $targetModel, $mapping): ModelHandler
    {
        $targetTable = $targetModel->schemaTable_get();
        return $this->leftJoin($targetTable, $mapping);
    }

    public function table(): SchemaTable
    {
        return $this->table;
    }

    public function trace(): ModelHandler
    {
        $this->table = $this->table->trace();
        return $this;
    }

    public function add(array $data)
    {
        $validatedData = $this->validate($data);
        if (!$validatedData) {
            return false;
        }
        if (count($validatedData) <= 0) {
            return false;
        }
        return $this->table->insert($validatedData);
    }

    public function validate(array $data, bool $checkNotNull = true)
    {
        return $this->schema()->fetchData($data, $checkNotNull);
    }

    public function update(array $newData, array $condition): int
    {
        if (count($newData) <= 0) {
            return false;
        }
        $validatedData = $this->validate($newData, false);
        if (count($condition) <= 0) {
            return false;
        }
        $table = $this->getTableWithCondition($condition);
        return $table->update($validatedData);
    }

    public function map(array $map): ModelHandler
    {
        $progressMapping = [];
        foreach ($map as $title => $fieldName) {
            if (is_array($fieldName)) {
                foreach ($fieldName as $fieldSubTitle => $fieldSubName) {
                    $subFieldMapping[$fieldSubTitle] = $this->mapFieldParser($fieldSubName);
                }
                $progressMapping[$title] = $subFieldMapping;
            } else {
                $progressMapping[$title] = $this->mapFieldParser($fieldName);
            }
        }
        $this->mappingSchema = $progressMapping;
        return $this;
    }

    public function mapMerge($fieldName): ModelHandler
    {
        if (count($this->mappingSchema) <= 0) {
            trigger_error('there must be a mapping for mapGroup');
            return $this;
        }
        $this->mapMergingPrototypeName = $this->mapFieldParser($fieldName);
        return $this;
    }

    private function mapFieldParser($fieldName)
    {
        if (strpos($fieldName, '.') == false) {
            return $fieldName;
        } else {
            list($tableName, $tableField) = explode('.', $fieldName);
            if ($tableName == $this->schema) {
                return $tableField;
            } else {
                return "schema_" . $tableName . "_" . $tableField;
            }
        }
    }

    public function groupBy($column)
    {
        $this->table = $this->table->group($column);
        return $this;
    }

    public function getById(int $id)
    {
        $idFieldName = $this->schema()->getName() . '.id';
        return $this->getOne([$idFieldName . '=?', $id]);
    }

    public function getOne(array $condition = [], $asc = true)
    {
        $result = $this->get($condition, 1, 0, $asc);
        if (is_array($result)) {
            return $result[0];
        }
        return $result;
    }

    public function getFirst(array $condition = [])
    {
        return $this->getOne($condition);
    }

    public function getLast(array $condition = [])
    {
        return $this->getOne($condition, false);
    }

    public function get(array $condition = [], $limit = false, $start = false, $asc = true)
    {
        $table = count($condition) > 0 ? $this->getTableWithCondition($condition) : $this->table;
        $asc = ($asc === false || $asc === "dasc") ? false : true;
        if (is_int($limit) && $limit > 0) {
            if (is_int($start)) {
                $table = $table->limit($limit, $start);
            } else {
                $table = $table->limit($limit);
            }
        }
        $schema = $this->schema();
        $resultList = $table
            ->order($schema->getName() . "." . $schema->getKey(), $asc)
            ->select($schema->selectList());
        return $this->fetch_schemaType($resultList);
    }

    public function count(array $condition = [])
    {
        $table = count($condition) > 0 ? $this->getTableWithCondition($condition) : $this->table;
        return $table->count();
    }

    public function sum(array $condition, $column)
    {
        $table = count($condition) > 0 ? $this->getTableWithCondition($condition) : $this->table;
        if (!strpos($column, '.')) {
            $column = $table->cookColumn($column);
        }
        $sum = $table->select("sum($column) as sum")[0]['sum'];
        return is_null($sum) ? 0 : $sum;
    }

    public function expect(array $condition = [], $number = 1)
    {
        return $number == $this->count($condition);
    }

    public function delete(array $condition)
    {
        if (count($condition) > 0) {
            $table =  $this->getTableWithCondition($condition);
        } else {
            return false;
        }
        return $table->delete();
    }

    public function dataObject_set($className)
    {
        if (is_object($className) && $className instanceof DataObject) {
            $className = get_class($className);
        }
        $this->dataObject = $className;
        return $this;
    }

    public function query(string $query, $data = [])
    {
        return $this->table->safeMode()->query($query, $data);
    }

    private function getTableWithCondition(array $condition)
    {
        if (
            count($condition) == 2 &&
            isset($condition[1])
        ) {
            return $this->table->where($condition[0], $condition[1]);
        }
        return $this->table->where($condition);
    }

    private function fetch_schemaType($resultList)
    {
        if (!$resultList) {
            return false;
        }
        $fetchSchema = count($this->mappingSchema) ? $this->mappingSchema : $this->schema()->fetchSchema();
        $jsonList = $this->schema()->getJsonList();
        $finalData = [];
        $groupedFinalData = [];

        foreach ($resultList as $row) {

            // handling groupBy if exists
            if ($this->mapMergingPrototypeName) {
                $keyName = $row[$this->mapMergingPrototypeName];
                if (!isset($groupedFinalData[$keyName])) {
                    $groupedFinalData[$keyName] = $this->fetch_row($fetchSchema, $row, $jsonList);
                } else {
                    foreach ($fetchSchema as $fieldName => $fieldSchema) {
                        if (is_array($fieldSchema)) {
                            $groupedFinalData[$keyName]->{$fieldName}[] = $this->fetch_row($fieldSchema, $row, $jsonList);
                        }
                    }
                }
            } else {
                $row = $this->fetch_row($fetchSchema, $row, $jsonList);
                if (class_exists($this->dataObject)) {
                    $row = new $this->dataObject($row);
                }
                $finalData[] = $row;
            }
        }
        if (!$finalData && isset($groupedFinalData)) {
            foreach ($groupedFinalData as $row) {
                if (class_exists($this->dataObject)) {
                    $row = new $this->dataObject($row);
                }
                $finalData[] = $row;
            }
        }
        return $finalData;
    }


    private function fetch_row($fetchSchema, $row, $jsonList = [])
    {
        $data = [];
        foreach ($fetchSchema as $name => $keyName) {
            if (is_array($keyName)) {
                $data[$name] = [$this->fetch_row($keyName, $row, $jsonList)];
            } elseif (is_object($keyName)) {
                if (isset($keyName->id) && is_null($row[$keyName->id])) {
                    $data[$name] = null;
                    continue;
                } else {
                    $data[$name] = $this->fetch_row($keyName, $row, $jsonList);
                }
            } else {
                $data[$name] = $row[$keyName];
            }
            if (count($jsonList) > 0) {
                if (in_array($keyName, $jsonList)) {
                    $data[$name] = json_decode($data[$name]);
                }
            }
        }
        return (object) $data;
    }
}
