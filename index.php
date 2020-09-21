<?php

use miladm\prototype\Schema;

include "vendor/autoload.php";


$a = new Schema('user_test');

$a->int('age')->notNull()->length(2);
$a->bigInt('registerNumber')->default(123);
$a->string('name');
$a->text('bio')->notNull()->default('here\'s my bio');
$a->boolean('activated')->default(0);
$a->email('email')->notNull()
    ->url('picture')
    ->hash('password')
    ->timestamp('birthdate')
    ->json('setting')
    ->object('posts', 'post');


$b = new Schema('post');
$b->string('title')
    ->text('content');

die(var_dump(
    $a->init_query()
));
