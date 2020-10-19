<?php

namespace miladm\prototype;

use miladm\Prototype;

defined('MILADM_PROTOTYPE_SCHEMA_SORT') ?: define('MILADM_PROTOTYPE_SCHEMA_SORT', TRUE);
class Schema
{
    public static $schemaNameList = [];

    private $engine = "InnoDB";
    private $tableName;
    private $primaryKey = false;
    private $lastFieldPointer = null;
    private $publicFieldList = [];
    private $fieldList = [];
    private $jsonFieldNameList = [];
    private $leftJoinList = [];

    function __construct($tableName, $defaultInit = true)
    {

        /*
            check schemaname has not be registered before if not register to general schema list
         */
        $schemaNameList = self::$schemaNameList;
        if (!isset($schemaNameList[$tableName])) {
            $schemaNameList[$tableName] = &$this;
        }
        self::$schemaNameList = $schemaNameList;
        $this->tableName = $tableName;
        if ($defaultInit) {
            $this->defaultInit();
        }
    }

    public function primaryKey($name)
    {
        $this->field_check($name);
        if ($this->primaryKey) {
            $oldName = $this->primaryKey->name;
            unset($this->publicFieldList[$oldName]);
        }
        $field = new PrimaryKeyField($name);
        $this->publicFieldList[$name] = &$field;
        $this->primaryKey = &$field;
        return $this;
    }

    public function int($name, $length = 11, $notNull = false, $unique = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("int", $name, $length, $notNull, $unique, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function private_int($name, $length = 11, $notNull = false, $unique = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("int", $name, $length, $notNull, $unique, false);
        return $this;
    }

    public function bigInt($name, $length = 11, $notNull = false, $unique = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("bigInt", $name, $length, $notNull, $unique, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function string($name, $length = 200, $notNull = false, $unique = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("string", $name, $length, $notNull, $unique, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function private_string($name, $length = 200, $notNull = false, $unique = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("string", $name, $length, $notNull, $unique, false);
        return $this;
    }

    public function text($name, $notNull = false, $unique = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("text", $name, 0, $notNull, $unique, false);
        $this->publicFieldList[$name] = $field;

        return $this;
    }

    public function private_text($name, $notNull = false, $unique = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("text", $name, 0, $notNull, $unique, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function boolean($name, $default = 0, $notNull = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("boolean", $name, 0, $notNull, false, $default);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function private_boolean($name, $default = 0, $notNull = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("boolean", $name, 0, $notNull, false, $default);
        return $this;
    }

    public function email($name): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("email", $name, 200, false, false, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function url($name): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("url", $name, 200, false, false, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function hash($name): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("hash", $name, 200, false, false, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function timestamp($name): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("timestamp", $name, 0, false, false, false);
        $this->publicFieldList[$name] = $field;
        return $this;
    }

    public function json($name): Schema
    {
        $this->field_check($name);
        $field = &$this->registerField("JSON", $name, 0, false, false, false);
        $this->publicFieldList[$name] = &$field;
        $this->jsonFieldNameList[] = $name;
        return $this;
    }

    /*
     * set fields types
     */
    public function object($name, Prototype $protoType, $mapping = "id", $notNull = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerObject($name, $protoType, $mapping, $notNull);
        $this->publicFieldList[$name] = &$field;
        $this->lastFieldPointer = &$field;
        $this->leftJoinList[] = (object)[
            "table" => $protoType->table(),
            "coverName" => $name,
            "mapping" => [
                $this->tableName . "." . $name,
                $name . ".id"
            ]
        ];
        return $this;
    }

    public function private_object($name, $schemaName, $leftJoinOn = "id", $notNull = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerObject($name, $schemaName, $leftJoinOn, $notNull);
        $this->lastFieldPointer = &$field;
        if (strpos($schemaName, "\\") !== false) {
            $schemaName = substr(
                $schemaName,
                strrpos($schemaName, '\\') + 1
            );
        }
        $this->leftJoinList[] = (object)["name" => $schemaName, "asName" => $name, "on" => $this->tableName . "." . $name . "=" . $name . ".id"];
        return $this;
    }

    public function hasMany($name, $protoType, $mapping = false, $notNull = false): Schema
    {
        $this->field_check($name);
        if ($mapping === false) {
            $mapping = $this->tableName;
        }
        $field = &$this->registerObject($name, $protoType, $mapping, $notNull);
        $this->publicFieldList[$name] = &$field;
        $this->lastFieldPointer = &$field;
        if (is_array($mapping) && count($mapping) == 2) {
            $mappingArray = $mapping;
        } else {
            $mappingArray = [
                $protoType->table()->name() . "." . $mapping,
                $this->tableName . "." . $this->primaryKey->name
            ];
        }
        $this->leftJoinList[] = (object)[
            "table" => $protoType->table(),
            "coverName" => $name,
            "mapping" => $mappingArray
        ];
        return $this;
    }

    /**
     * make current field private
     */
    public function private(): Schema
    {
        if (is_null($this->lastFieldPointer))
            return $this;
        $fieldName = $this->lastFieldPointer->name;
        unset($this->publicFieldList[$fieldName]);
        return $this;
    }

    /**
     * set current field as unique
     */
    public function unique(): Schema
    {
        if (is_null($this->lastFieldPointer))
            return $this;
        $this->lastFieldPointer->unique = true;
        return $this;
    }

    /**
     * set current field as notNull
     */
    public function notNull(): Schema
    {
        if (is_null($this->lastFieldPointer))
            return $this;
        $this->lastFieldPointer->notNull = true;
        return $this;
    }

    /**
     * set a default value for current field
     * ---------note: intelliphence document formatter 
     * -------------- puts default in next line
     */
    public function
    default($value): Schema
    {
        if (is_null($this->lastFieldPointer))
            return $this;
        $this->lastFieldPointer->default = $value;
        return $this;
    }

    /**
     * set length for current field
     */
    public function length(int $value): Schema
    {
        if (is_null($this->lastFieldPointer))
            return $this;
        $this->lastFieldPointer->length = $value;
        return $this;
    }

    /*
        query to be runned on creating database on the firs run (init mode run)
     */
    public function init_queryList()
    {
        $queryList[] = "create table if not exists `$this->tableName` (
            " . $this->init_query_keys() . "
            `createTime` timestamp not null default current_timestamp,
            `updateTime` timestamp default current_timestamp ON UPDATE CURRENT_TIMESTAMP,
            " . $this->init_query_keyConfigurations() . "
       )engine=$this->engine default charset = utf8 collate=utf8_general_ci auto_increment=1;";
        $queryList = array_merge($queryList, $this->init_alter_fields_query());
        return $queryList;
    }

    public function getName()
    {
        return $this->tableName;
    }

    public function getKey()
    {
        return $this->primaryKey->name;
    }

    /*
        list of the public keys and select list of objects
        note it goes only one level inside object connections
     */
    public function selectList($coverName = false, $skipObjects = false)
    {
        $schemaNameList = self::$schemaNameList;
        $selectList = [];
        $fieldList = $this->publicFieldList;
        $this->sort($fieldList);
        foreach ($fieldList as $name => $field) {
            if ($field->type == "object" && !$skipObjects) {
                $objectSelectList = $this->selectStringFromObject($field, $schemaNameList);
                $selectList = array_merge($objectSelectList, $selectList);
            } else {
                $selectList[] = $this->selectString($coverName, $name);
            }
        }
        return $selectList;
    }

    public function getJsonList()
    {
        return count($this->jsonFieldNameList) ? $this->jsonFieldNameList : [];
    }

    /*
        get the list of leftJoin for this shcema on selection
     */
    public function leftJoinList()
    {
        return $this->leftJoinList;
    }


    /*
        return the schema and mapping to scheme the result rows
        note it goes only one level inside object connections
     */
    public function fetchSchema($cover = false, $preventObjects = false)
    {
        $schemaNameList = self::$schemaNameList;
        $publicFieldList = $this->publicFieldList;
        $this->sort($publicFieldList);
        foreach ($publicFieldList as $columnName => $field) {
            if ($field->type == "object" && !$preventObjects) {
                $fetchSchema[$columnName] = (object)$field->schema->fetchSchema($field->name, true);
            } else {
                if ($cover) {
                    $fetchSchema[$columnName] = "schema_" . $cover . "_" . $columnName;
                } else {
                    $fetchSchema[$columnName] = /*$this->tableName . "." . */ $columnName;
                }
            }
        }
        return $fetchSchema;
    }

    private function selectString($coverName, $columnName)
    {
        if ($coverName) {
            return $coverName . "." . $columnName . " as " . "schema_" . $coverName . "_" . $columnName;
        }
        return $this->tableName . "." . $columnName;
    }

    private function selectStringFromObject(ObjectField $field, $schemaNameList)
    {
        $selectList = [];

        $fieldList = $field->schema->selectList($field->name, true);
        $this->sort($fieldList);
        foreach ($fieldList as $value) {
            $selectList[] = $value;
        }

        // updating jsonName list
        $objectJsonNameList = $field->schema->getJsonList();
        if ($objectJsonNameList) {
            foreach ($objectJsonNameList as $value) {
                $this->jsonFieldNameList[] = "schema_" . $field->name . "_" . $value;
            }
        }
        return $selectList;
    }

    private function sort(&$array)
    {
        if (MILADM_PROTOTYPE_SCHEMA_SORT) {
            ksort($array);
        }
    }

    private function init_query_keys()
    {
        return "`" . $this->primaryKey->name . "` int(" . $this->primaryKey->length . ") not null auto_increment,";
    }

    private function init_query_keyConfigurations()
    {
        return "primary key(`" . $this->primaryKey->name . "`)";
    }

    private function init_alter_fields_query()
    {
        $string = [];
        $fieldList = $this->fieldList;
        $this->sort($fieldList);
        foreach ($fieldList as $name => $schema) {
            $string[] = "ALTER TABLE `$this->tableName` ADD " . $this->init_query_field_string($schema) . ";";
            if ($schema->type == 'object') {
                $string[] = "ALTER TABLE `$this->tableName` ADD INDEX `" . $schema->name . "` (`" . $schema->name . "`);";
            }
        }
        return $string;
    }

    private function init_query_field_string($schema)
    {

        //name
        $r = "`$schema->name`";
        $r .= " ";

        //type and length
        switch ($schema->type) {
            case "int":
                $r .= "int($schema->length)";
                break;
            case "string":
            case "email":
            case "url":
            case "hash":
                $r .= "varchar($schema->length)";
                break;
            case "object":
                $r .= "int(" . $this->primaryKey->length . ")";
                break;
            case 'JSON':
                $r .= 'text';
                break;
            default:
                $r .= $schema->type;
        }
        $r .= " ";

        //not null
        if ($schema->notNull) {
            $r .= "not null";
            $r .= " ";
        }

        //unique
        if ($schema->unique) {
            $r .= "unique";
            $r .= " ";
        }
        if ($schema->default !== false) {
            if ($schema->default === 'CURRENT_TIMESTAMP') {
                $r .= "default " . $schema->default . "";
            } else {
                $r .= "default '" . $schema->default . "'";
            }
            $r .= " ";
        }
        // $r .= ",
        //     ";
        return $r;
    }

    /*
        default init for now we have
            createTime
            updateTime
            id as the primary key
     */
    private function defaultInit()
    {
        $this->publicFieldList = [
            "createTime" => (object)["type" => "timestamp"],
            "updateTime" => (object)["type" => "timestamp"]
        ];

        //setting up the primarykey
        $this->primaryKey("id");
    }

    private function field_check($name)
    {
        if (in_array($name, ["createTime", "updateTime"]) || isset($this->fieldList[$name])) {
            trigger_error("invalid name for field " . $name);
        }
    }

    /*
        setting engine on the init database
     */
    public function engine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * insert data into schema template
     * validate data input
     *  trigger error on non validated
     * check for required fields
     *  trigger error on not set required fields
     */
    public function fetchData(array $data, bool $checkNotNull = true)
    {
        $returnData = [];
        foreach ($this->fieldList as $fieldName => $template) {

            // check if input is required and isset in data
            if ($template->notNull && $template->default === false && $checkNotNull) {
                if (!isset($data[$fieldName]) || is_null($data[$fieldName])) {
                    return false; // $this->error('fetchData', "$fieldName required but has not been set in data");
                }
            }
            // if (isset($data[$fieldName]) && !is_null($data[$fieldName])) {
            // $value = $data[$fieldName];

            // validate datatype
            // $validationResult = \prototype\Validate::check($value, $template->type);
            // if (!$validationResult)
            //     return false;

            // if everything is alright then push to return data array
            if (isset($data[$fieldName]) && !is_null($data[$fieldName])) {
                $returnData["$this->tableName.$fieldName"] = $this->cookData($data[$fieldName], $template->type);
            }
            // }
        }
        // if there's any id entered then take care of it
        if (isset($data['id']) && is_int($data['id'])) {
            $returnData['id'] = $data['id'];
        }
        return $returnData;
    }

    // /**
    //  * get data and the type and do anything on data if necessary
    //  */
    private function cookData($data, $dataType)
    {
        switch ($dataType) {
            case 'hash':
                return md5($data . '//here goes hashcode');
            case 'string':
                return (string)$data;
            case 'object':
                if (is_int($data)) {
                    return $data;
                } elseif (is_string($data)) {
                    return (int)$data;
                } else {
                    return $data->id;
                }
            case 'boolean':
                return $data ? 1 : 0;
            case 'JSON':
                return json_encode($data);

            default:
                return $data;
        }
    }

    /*
        registering normal type of fields
     */
    private function &registerField($type, $name, $length, $notNull, $unique, $default)
    {
        $field = new DatabaseField();
        $field->type = $type;
        $field->name = $name;
        $field->length = $length;
        $field->notNull = $notNull;
        $field->unique = $unique;
        $field->default = $default;
        $this->fieldList[$name] = &$field;
        $this->lastFieldPointer = &$field;
        return $field;
    }

    /*
        register object type of fields
     */
    private function &registerObject($name, Prototype $protoType, $mapping, $notNull)
    {
        $field = new ObjectField();
        $field->type = "object";
        $field->name = $name;
        $field->schema = $protoType->schema();
        // if (isset($namespace)) {
        //     $field->namespace = $namespace;
        // }
        $field->mapping = $mapping;
        $field->notNull = $notNull;
        $this->fieldList[$name] = &$field;
        return $field;
    }
}
