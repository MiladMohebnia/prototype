<?php

/**
 * ALTER TABLE `votes` ADD UNIQUE `unique_index`(`user`, `email`, `address`);
 * prototype query trace
 * 
 */

use miladm\Prototype;
use miladm\prototype\ModelHandler;
use miladm\prototype\Schema;
use miladm\table\Connection;

include "vendor/autoload.php";

define('MILADM_PROTOTYPE_SCHEMA_SORT', true);

class MainConnection extends Connection
{
    public $host = "127.0.0.1";
    public $databaseName = "sample";
    public $user = 'root';
    public $password = 'root';
}

class Security
{
    public static function hash($data)
    {
        return 'aaa' . md5($data);
    }
}

class UserP extends Prototype
{
    public function init(): Schema
    {
        return $this->schema('user')
            ->string('name')
            ->email('email')
            ->hash('password')->hashFunction(fn ($data) => Security::hash($data))
            ->json('something');
    }

    public function connection(): Connection
    {
        return new MainConnection;
    }
}

class DataObject
{
    protected $__data__ = [];

    function __set($name, $value)
    {
        if (!property_exists($this, $name)) {
            $this->__data__[$name] = $value;
        } else {
            if (isset($this->$name) && is_string($this->$name) && class_exists($this->$name)) {
                $this->$name = new $this->$name($value);
            } elseif (isset($this->$name) && is_object($this->$name)) {
                if ($this->$name instanceof DataObject) {
                    $this->$name = new $this->$name($value);
                } else {

                    // bad implementation
                    /**
                     * Todo: update this part
                     */
                    foreach ((array) $this->$name as $key => $val) {
                        $this->$name->$key = new $val($value->$key);
                    }
                }
            } else {
                $this->$name = $value;
            }
        }
        return $this;
    }

    function __get($name)
    {
        return $this->$name ?? $this->__data__[$name] ?? null;
    }

    function __construct($data = false)
    {
        $this->init();
        if ($data) {
            $this->injectData($data);
        }
    }

    public function init()
    {
    }

    public function injectData($data)
    {
        $data = (array) $data;
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
        return $this;
    }
}

interface onBeforeSave
{
    function onBeforeSave(array $data): array;
}

interface onBeforeSet
{
    function onBeforeSet($name, $value): mixed;
}

abstract class DataObjectUnit extends DataObject
{
    protected $__updateData__ = [];
    private $__dataFixed__ = false;
    private ?array $__condition__ = null;

    protected function onBeforeSet($name, $value): mixed
    {
        return $value;
    }

    function __set($name, $value)
    {
        $value = $this->onBeforeSet($name, $value);
        if ($this->__get($name) !== $value) {
            if ($this->__dataFixed__) {
                $this->__updateData__[$name] = $value;
            } else {
                parent::__set($name, $value);
            }
        }
    }

    function __get($name)
    {
        return $this->__updateData__[$name] ?? parent::__get($name);
    }

    abstract function model(): ModelHandler;

    function load(array $condition)
    {
        if (!$condition) {
            return false;
        }
        $this->__condition__ = $condition;
        $data = $this->model()->get($this->__condition__);
        if (!$data || count($data) == 0) {
            return false;
        } else if (count($data) > 1) {
            // throw new Exception('multiple results for entered $condition');
            return false;
        }
        $this->injectData($data[0]);
        $this->__dataFixed__ = true;
        return $this;
    }

    function isStaged(): bool
    {
        return count($this->__updateData__) ? true : false;
    }

    function onBeforeSave(array $data): array
    {
        return $data;
    }

    function save(): bool
    {
        if (!$this->isStaged()) {
            return false;
        }
        if (!$this?->__condition__) {
            return false;
        }
        $updateDataList = (array) $this->__updateData__;
        $updateDataList = $this->onBeforeSave($updateDataList);
        return $this->model()->update($updateDataList, $this->__condition__) ? true : false;
    }

    function dataFixed(): bool
    {
        return $this->__dataFixed__;
    }
}

class User extends DataObjectUnit implements onBeforeSet
{
    protected string $name;
    protected string $email;
    protected ?string $something;
    protected string $password;

    function model(): ModelHandler
    {
        return UserP::model();
    }

    function checkPassword(string $password)
    {
        return $this->password == Security::hash($password);
    }

    function onBeforeSet($name, $value): mixed
    {
        if ( // if updating pass word and it's the same as it was
            $this->dataFixed() &&
            $name == 'password' &&
            isset($this->password)  &&
            $this->checkPassword($value)
        ) {
            return Security::hash($value); //skips input
        }
        return $value;
    }
}


$user = new User();
$user->load(['id' => 1]);
$user->password = 'milado';
die(var_dump(
    $user,
    $user->isStaged(),
    $user->save()
));
