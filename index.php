<?php

/**
 * ALTER TABLE `votes` ADD UNIQUE `unique_index`(`user`, `email`, `address`);
 * prototype query trace
 * 
 */

use miladm\DataObject;
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


// $DO = new DataObject;
// $object = (object) [
//     "name" => 'milad',
//     'age' => 28
// ];
// $DO->injectData($object);

// die(var_dump(
//     $DO,
//     $DO->name
// ));


// // part 2
// class UserDataObject extends DataObject
// {
//     public $name;
//     public $age;
// }
// $DO = new UserDataObject;
// $object = (object) [
//     "name" => 'milad',
//     'age' => 28
// ];
// $DO->injectData($object);

// die(var_dump(
//     $DO,
//     $DO->name
// ));



// // part 3
// class UserDataObject extends DataObject
// {
//     public $name;
//     public $email;
// }

// $user = User::dataObject(UserDataObject::class)->get();
// foreach ($user as $u) {
//     (var_dump(
//         $u,
//         $u->name,
//         $u->updateTime,
//         "---------------------------------------------------------------"
//     ));
// }
// die;

// part 4

class UserDataObject extends DataObject
{
    public $name;
    public $email;

    public function isAdmin()
    {
        return $this->name === "admin";
    }
}

class PostDataObject extends DataObject
{
    public $title;
    public $content;
    public $user;

    public function init()
    {
        $this->user  = new UserDataObject();
    }

    public function hasAccess()
    {
        if ($this->user->isAdmin()) {
            return true;
        }
        return false;
    }
}

class User2 extends User
{
    public function defaultDataObject()
    {
        return new UserDataObject();
    }
}
class Post2 extends Post
{
    public function defaultDataObject()
    {
        return new PostDataObject();
    }
}

// $user = User2::get();
$postList = Post2::get();
foreach ($postList as $post) {
    (var_dump(
        $post,
        $post->user->name,
        $post->user->isAdmin(),
        $post->hasAccess(),
        "---------------------------------------------------------------"
    ));
}
die;




die(json_encode(
    [
        // User::create(),
        // Post::create(),
        // User::update([
        //     'email' => 'miladmohebnia@gmail.com',
        //     'password' => 'jkdsafh'
        // ],  ["name=?", ['milad']])
        // User::create(),
        User::get()
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
