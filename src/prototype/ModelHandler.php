<?php

namespace miladm\prototype;

use miladm\table\Connection;

class ModelHandler
{
    /** @var SchemaTable table*/
    private $table = null;
    private $schema = false;
    private $connection = false;


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
        $this->table = new SchemaTable($this->schema(), $this->connection);
        $this->table->fetchArray();

        // left-join all object fields in schema
        foreach ($this->schema()->leftjoinList() as $value) {
            $targetTable = $value->table->coverName($value->coverName);
            $this->table = $this->table->leftjoin($targetTable, $value->mapping);
        }
        return true;
    }

    public function table(): SchemaTable
    {
        return $this->table;
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
        $table = $this->table;
        if (count($condition) == 2) {
            $table = $table->where($condition[0], $condition[1]);
        }
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

        return $this->fetch_shemaType($resultList);
    }

    public function count(array $condition = [])
    {
        if (count($condition) == 2) {
            $table = $this->table->where($condition[0], $condition[1]);
        } else {
            $table = $this->table;
        }
        return $table->count();
    }

    public function sum(array $condition = [], $column)
    {
        $table = $this->table;
        if (count($condition) > 0) {
            $table = $table->where($condition[0], $condition[1]);
        }
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

    public function delete(array $condition = [])
    {
        // $table = $this->table;
        // if (count($condition) > 0) {
        //     $table = $table->where($condition[0], $condition[1]);
        // } else {
        //     return false;
        // }
        // return $table->delete();
    }

    private function fetch_shemaType($resultList)
    {
        if (!$resultList) {
            return false;
        }
        // if ($this->resultMapping) {
        //     $fetchSchema = $this->resultMapping;
        // } else {
        $fetchSchema = $this->schema()->fetchSchema();
        // }
        $jsonList = $this->schema()->getJsonList();
        $finalData = [];
        foreach ($resultList as $row) {

            // handling groupBy if exists
            // if ($this->mapGroupFields) {
            //     $keyName = $row[$this->mapGroupFields];
            //     if (!isset($groupedFinalData[$keyName])) {
            //         $groupedFinalData[$keyName] = $this->fetch($fetchSchema, $row, $jsonList);
            //     } else {
            //         foreach ($fetchSchema as $fieldName => $fieldSchema) {
            //             if (is_array($fieldSchema)) {
            //                 $groupedFinalData[$keyName]->{$fieldName}[] = $this->fetch($fieldSchema, $row, $jsonList);
            //             }
            //         }
            //     }
            // } else {
            $finalData[] = $this->fetch_row($fetchSchema, $row, $jsonList);
            // }
        }
        if (!$finalData && isset($groupedFinalData)) {
            foreach ($groupedFinalData as $row) {
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
