<?php

namespace SimpleRouter\Tests;

use PHPUnit\Framework\TestCase;
use SimpleRouter\Exceptions\HandlerAlreadyExistException;
use SimpleRouter\Response;
use SimpleRouter\Router;

class RouterTest extends TestCase
{
    public function testAddRemoveHandler()
    {
        $router = new Router();
        $router->respond("GET");
        $router->respond("GET","/hello");
        $router->respond("POST","/hello");
        $router->respond("PUT","/hello");
        $router->respond("DELETE","/hello");
        $router->respond("HEAD","/hello");
        $router->respond("OPTION","/hello");
        $router->respond("GET","/world");
        $router->respond("POST","/world");
        $router->removeHandler("GET","/hello");
        $router->respond("GET","/hello");
        $this->assertEquals(9, $router->getRouteNum());
    }
    public function testAddHandlerTwice()
    {
        $this->expectException(HandlerAlreadyExistException::class);
        $router = new Router();
        $router->respond("GET");
        $router->respond("GET","/hello");
        $router->respond("GET","/hello");
    }

    public function testFakeAccess()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectNotToPerformAssertions();

        $response = new Response();
        $router = new Router();
        $router->dispath();
    }
}