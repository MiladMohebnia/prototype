# configurations

Just install package using `composer` and load `autoloader` into your `index.php` file

```
composer require miladm/prototype
```

index.php

```php
<?php

include 'vendor/autoload.php';
```

# create model (prototype)

```php
use miladm\Prototype;

class UserModel extends Prototype
{

}
```

here we extend `Prototype`

## create connection

to create connection create a class as below

```php
use miladm\table\Connection;

class MainConnection extends Connection
{
    public $host = "127.0.0.1";
    public $databaseName = "sample";
    public $user = 'root';
    public $password = 'root';
}
```

and setup your connection into prototype as below

```php
class UserModel extends Prototype
{
    public function connection()
    {
        return new MainConnection;
    }
}
```

## insert schema

to create schema we have to setup schema in `init` method pf class

```php
class UserModel extends Prototype
{
    public function connection()
    {
        return new MainConnection;
    }

    public function init()
    {
        // schema goes here
    }
}
```

### create schema

there's a method in `Prototype` parent called `schema` as below

```php
    schema($name = false): Schema
```

`name` parameter is the name of this prototype in your database. for example if you have a table named `user_data`
in database and you want to create `UserModel` prototype you have to put `user_data` as name into schema method.

```php
class UserModel extends Prototype
{
    public function init()
    {
        $this->schema('user_data');
    }
}
```

to create schema you have to setup architecture of the table. to config table architecture you have to call methods of `Schema`.
to understand better we go through an example

```php
    public function init()
    {
        $this->schema('user_data')->string('name')->string('family')->int('age');
    }
```

in this example we have three fields for table `user_data` and they are `name` in type if string and `family` in type of string
and `age` in type of number. by type string we mean `varchar` in mariadb database and by type number we mean `int`.

> Important note: all schema has `id:int`, `create_time:timestamp` and `update_time:timestamp` by default. you don't need to add any.

there are more field types for your architecture. you can follow as table below:

| method    | parameters                       | createsType | description                                                |
| --------- | -------------------------------- | ----------- | ---------------------------------------------------------- |
| int       | name:string                      | int         | creates integer field                                      |
| bigInt    | name:string                      | bigInt      | creates integer with more capacity field                   |
| string    | name:string                      | varchar     | creates string type field                                  |
| text      | name:string                      | text        | create text type field (more capacity string)              |
| boolean   | name:string                      | boolean     | create boolean field (0 or 1)                              |
| email     | name:string                      | varchar     | creates string type field with email validation            |
| url       | name:string                      | varchar     | creates string type field with url validation              |
| hash      | name:string                      | varchar     | creates string type field. data will hash before insertion |
| timestamp | name:string                      | timestamp   | timestamp can hold exact time. got for dates and times     |
| json      | name:string                      | text        | stores json encoded data and decode data on selection      |
| object    | name:string, prototype:Prototype | int         | stores foreign key. good for relations between prototypes  |

**Example**

```php
    public function init()
    {
        $this->schema('user_data')
            ->string('name')
            ->string('family')
            ->int('age')
            ->email('email')
            ->hash('password')
            ->boolean('verified');
    }
```

> this documentation is under construction but code talks for itself. feel free to read and use the code!
