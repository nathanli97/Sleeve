<?php

use PHPUnit\Framework\TestCase;
use \SimpleRouter\Request;

class RequestTest extends TestCase
{
    public function testCreateFromEnvironment()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/fake_access';
        $_GET = array(
            'key' => 'value'
        );
        $_POST = array(
            'k' => 'v'
        );
        $request = Request::createFromEnvironment();
        $this->assertEquals($_GET, $request->get_params);
        $this->assertEquals($_POST, $request->post_params);
        $this->assertEquals($_SERVER, $request->server);
        $this->assertEquals($_COOKIE, $request->cookies);
        $this->assertEquals($_FILES, $request->files);
        $this->assertEquals('', $request->body);
        $this->assertEquals('POST', $request->method);
    }
}