<?php

/**
 * ALTER TABLE `votes` ADD UNIQUE `unique_index`(`user`, `email`, `address`);
 * prototype query trace
 * 
 */

use miladm\Prototype;
use miladm\prototype\Schema;
use miladm\table\Connection;

include "vendor/autoload.php";

define('MILADM_PROTOTYPE_SCHEMA_SORT', true);

class MainConnection extends Connection
{
    public $host = "172.19.0.2";
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

    public static function posts()
    {
        return self::hasMany(new Post, ['post_2.user', 'user_2.id']);
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
        // Post::delete(['id' => 2]),
        User::posts()->get()
        // User::create(),
        // Post::create(),
        // User::update([
        //     'email' => 'miladmohebnia@gmail.com',
        //     'password' => 'jkdsafh'
        // ],  ["name=?", ['milad']])
        // User::create(),
        // User::get(['a=?&b=?', [12, 5]]),
        // User::get(['a' => ['c' => 1, 'd' => 4], 'b' => 7]),
        // Post::map([
        //     'user' => 'user.name',
        //     'post' => [
        //         'title' => 'title'
        //     ]
        // ])->mapMerge('user.id')->get()
    ],
    JSON_PRETTY_PRINT
));



die(var_dump(
    User::posts()
    // Post::create(),
    // User::create()
    // Post::getLast()
    // User::model()->schema()->fetchSchema(),
    // Post::model()->schema()->fetchSchema(),
));
