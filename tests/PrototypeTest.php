<?php

use PHPUnit\Framework\TestCase;

include __DIR__ . "/config.php";

final class PrototypeTest extends TestCase
{
    public function testCreateTable()
    {
        $this->assertSame(null, User::create());
    }

    public function testInsertion()
    {
        $insert = User::add([
            'name' => 'jack',
            'email' => 'paulse',
            'password' => 'romantic123'
        ]);
        $this->assertNotFalse($insert);
        $this->assertNotNull($insert?->id);
        $user = User::getOne(["id" => $insert->id]);
        $this->assertNotFalse(
            $user
        );
    }

    public function testSelection()
    {
        $this->assertSame(1, (int) User::sum([], 'id'), 'sum');
    }

    public function testTerminate()
    {
        $this->assertNull(User::terminate());
    }
}
