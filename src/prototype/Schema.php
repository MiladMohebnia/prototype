<?php

namespace miladm\prototype;

defined('MILADM_PROTOTYPE_SCHEMA_SORT') ?: define('MILADM_PROTOTYPE_SCHEMA_SORT', TRUE);
class Schema
{
    public static $schemaNameList = [];

    private $engine = "InnoDB";
    private $fieldList = [];
    private $tableName;
    private $jsonFieldNameList = [];
    private $lastFieldPointer = null;
    private $leftJoinList = [];
    private $publicFieldList = [];
    private $primaryKey = false;

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
    public function object($name, $schemaName, $leftJoinOn = "id", $notNull = false): Schema
    {
        $this->field_check($name);
        $field = &$this->registerObject($name, $schemaName, $leftJoinOn, $notNull);
        $this->publicFieldList[$name] = &$field;
        $this->lastFieldPointer = &$field;
        if (strpos($schemaName, "\\") !== false) {
            $schemaName = $schemaName::schemaName();
        }
        $this->leftJoinList[] = (object)["name" => $schemaName, "asName" => $name, "on" => $this->tableName . "." . $name . "=" . $name . ".id"];
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
    public function init_query()
    {
        return "
        create table if not exists `$this->tableName` (
            " . $this->init_query_keys() . "
            " . $this->init_query_fields() . "
            `createTime` timestamp not null default current_timestamp,
            `updateTime` timestamp default current_timestamp ON UPDATE CURRENT_TIMESTAMP,
            " . $this->init_query_keyConfigurations() . "
       )engine=$this->engine default charset = utf8 collate=utf8_general_ci auto_increment=1;
        ";
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
    public function selectList($asName = false, $preventObjects = false)
    {
        $schemaNameList = self::$schemaNameList;
        $selectList = [];
        $fieldList = $this->publicFieldList;
        $this->sort($fieldList);
        foreach ($fieldList as $name => $field) {
            if ($field->type == "object" && !$preventObjects) {
                $objectSelectList = $this->selectStringFromObject($field, $schemaNameList);
                $selectList = array_merge($objectSelectList, $selectList);
            } else {
                $selectList[] = $this->selectString($asName, $name);
            }
        }
        return $selectList;
    }

    public function getJsonList()
    {
        return count($this->jsonFieldNameList) ? $this->jsonFieldNameList : [];
    }

    private function selectString($coverName, $columnName)
    {
        if ($coverName) {
            return $coverName . "." . $columnName . " as " . "schema_" . $coverName . "_" . $columnName;
        }
        return $this->tableName . "." . $columnName;
    }

    private function selectStringFromObject($field, $schemaNameList)
    {
        $selectList = [];
        if (!isset($schemaNameList[$field->schemaName])) {
            trigger_error('see me!');
            if (!is_null($field->namespace)) {
                if (class_exists(strtolower($field->namespace) . $field->schemaName)) {
                    call_user_func(strtolower($field->namespace) . $field->schemaName . '::start');
                }
            } elseif (class_exists($field->schemaName)) {
                call_user_func($field->schemaName . '::start');
            } elseif (class_exists('\\' . strtolower($field->schemaName) . '\\' . $field->schemaName)) {
                call_user_func('\\' . strtolower($field->schemaName) . '\\' . $field->schemaName . '::start');
            } else {
                trigger_error("the schema name $field->schemaName is invalid!");
            }
        }
        $fieldList = $schemaNameList[$field->schemaName]->selectList($field->name, true);
        $this->sort($fieldList);
        foreach ($fieldList as $value) {
            $selectList[] = $value;
        }

        // updating jsonName list
        $objectJsonNameList = $schemaNameList[$field->schemaName]->getJsonList();
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

    private function init_query_fields()
    {
        $string = "";
        $fieldList = $this->fieldList;
        $this->sort($fieldList);
        foreach ($fieldList as $name => $schema)
            $string .= $this->init_query_field_string($schema);
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
        $r .= ",
            ";
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
    private function &registerObject($name, $schemaName, $leftJoinOn, $notNull)
    {
        if (strpos($schemaName, "\\") !== false) {
            $namespace = substr(
                $schemaName,
                0,
                strrpos($schemaName, '\\') + 1
            );
            $schemaName = $schemaName::schemaName();
        }
        $field = new ObjectField();
        $field->type = "object";
        $field->name = $name;
        $field->schemaName = $schemaName;
        if (isset($namespace)) {
            $field->namespace = $namespace;
        }
        $field->leftJoinOn = $leftJoinOn;
        $field->notNull = $notNull;
        $this->fieldList[$name] = &$field;
        return $field;
    }
}
