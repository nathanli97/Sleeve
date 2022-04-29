<?php

namespace Sleeve\Tests;

use PHPUnit\Framework\TestCase;
use Sleeve\Exceptions\InvalidEnvironmentException;
use Sleeve\Request;

/**
 * The test of request class
 */
class RequestTest extends TestCase
{
    /**
     * Test if the router can create request from environment(e.g. Apache web server)
     * @return void
     * @throws InvalidEnvironmentException
     */
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
        $this->assertEquals($_POST, $request->body_params);
        $this->assertEquals($_SERVER, $request->server);
        $this->assertEquals($_COOKIE, $request->cookies);
        $this->assertEquals($_FILES, $request->files);
        $this->assertEquals('', $request->body);
        $this->assertEquals('POST', $request->method);
    }
}
