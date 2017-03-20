<?php
namespace Tests\Integration;

use Tests\TestCase;

class Login extends TestCase
{

    public function testHome()
    {
        $response = $this->get('admin');
        $response->assertResponseStatus(200);
    }
}
