<?php

use miladm\Prototype;
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

class User extends Prototype
{
    public function init(): Schema
    {
        return $this->schema('user_2')
            ->string('name')
            ->email('email')
            ->hash('password')->private()
            ->json('something');
    }

    public function connection(): Connection
    {
        return new MainConnection;
    }
}

class Post extends Prototype
{
    public function init(): Schema
    {
        return $this->schema('post_2')
            ->string('title')
            ->text('content')
            ->object('user', new User);
    }

    public function connection(): Connection
    {
        return new MainConnection;
    }
}

die(json_encode(
    [
        // User::update([
        //     'email' => 'miladmohebnia@gmail.com',
        //     'password' => 'jkdsafh'
        // ],  ["name=?", ['milad']])
        // User::create(),
        // User::get(['a=?&b=?', [12, 5]]),
        User::get(['a' => ['c' => 1, 'd' => 4], 'b' => 7]),
    ],
    JSON_PRETTY_PRINT
));



die(var_dump(
    // Post::create(),
    // User::create()
    Post::getLast()
    // User::model()->schema()->fetchSchema(),
    // Post::model()->schema()->fetchSchema(),
));
