<?php

use miladm\Prototype;
use miladm\prototype\Schema;
use miladm\table\Connection;

class MainConnection extends Connection
{
    public $host = "127.0.0.1";
    public $databaseName = "sample";
    public $user = 'root';
    public $password = 'root';
}

class User extends Prototype
{
    public static string $dynamic_name;
    public function init(): Schema
    {
        self::$dynamic_name = 'test_' . time();
        return $this->schema(self::$dynamic_name)
            ->string('name')
            ->email('email')
            ->hash('password')->private()
            ->json('something');
    }

    public function connection(): Connection
    {
        return new MainConnection;
    }

    public static function posts()
    {
        return self::hasMany(new Post, ['post_2.user', 'user_2.id']);
    }

    public static function terminate()
    {
        self::query("drop table " . self::$dynamic_name);
    }
}
