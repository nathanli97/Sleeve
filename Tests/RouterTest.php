<?php

namespace Sleeve\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sleeve\Exceptions\HandlerAlreadyExistException;
use Sleeve\Exceptions\RespondAlreadySentException;
use Sleeve\Request;
use Sleeve\Response;
use Sleeve\Router;

class RouterTest extends TestCase
{
    protected static function getMethodOfClass($cname, $name)
    {
        $class = new ReflectionClass($cname);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testAddRemoveHandler()
    {
        $router = new Router();
        $router->respond("GET");
        $router->respond("GET", "/hello");
        $router->respond("POST", "/hello");
        $router->respond("PUT", "/hello");
        $router->respond("DELETE", "/hello");
        $router->respond("HEAD", "/hello");
        $router->respond("OPTION", "/hello");
        $router->respond("GET", "/world");
        $router->respond("POST", "/world");
        $router->removeHandler("GET", "/hello");
        $router->respond("GET", "/hello");
        $this->assertEquals(9, $router->getRouteNum());
    }
    public function testAddHandlerTwice()
    {
        $this->expectException(HandlerAlreadyExistException::class);
        $router = new Router();
        $router->respond("GET");
        $router->respond("GET", "/hello");
        $router->respond("GET", "/hello");
    }

    public function testFakeAccess()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectNotToPerformAssertions();

        $response = new Response();
        $router = new Router();
        $router->dispatch();
    }

    public function testDispatchTwice()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectException(RespondAlreadySentException::class);

        $router = new Router();
        $router->dispatch(null, $response);
        $router->dispatch(null, $response);
    }

    public function testDispatchTwiceGlobal()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectException(RespondAlreadySentException::class);

        $router = new Router();
        $router->dispatch();
        $router->dispatch();
    }

    public function testRemoveGetParamFromUrl()
    {
        $router = new Router();
        $targetMethod = self::getMethodOfClass('Sleeve\Router', 'removeGetParamFromUrl');
        $this->assertEquals(
            '/www/index.php/hello_world',
            $targetMethod->invokeArgs($router, array("/www/index.php/hello_world?key=1234"))
        );
    }

    public function testAccess404()
    {
        $router = new Router();
        $respond = $router->dispatch(null, $respond, false);

        $this->assertEquals(404, $respond->status_code);
    }

    public function testAccess404Sent()
    {
        $router = new Router();
        $respond = $router->dispatch(null, $respond);

        $this->assertEquals(404, $respond->status_code);
        $this->assertEquals(true, $respond->isSent());
    }

    public function testAccessIndex()
    {
        $router = new Router();
        $router->respond('GET', '/', function () {
            return 'hello, world!';
        });
        $request = new Request('GET');
        $request->url = '/';
        $respond = $router->dispatch($request, $respond, false);

        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('hello, world!', $respond->body);
    }

    public function testAccessPageFromDifferentHandler()
    {
        $router = new Router();
        $router->respond('GET', '/', function () {
            return 'hello, world!';
        });
        $router->respond('GET', '/test', function () {
            return 'test';
        });
        $router->respond('GET', '/test2', function () {
            return 'test2';
        });
        $router->respond('GET', '/test/.+', function () {
            return 'test3';
        });
        $router->respond('GET', '/test4', function () {
            return 'test4';
        });

        $request = new Request('GET');

        $request->url = '/';
        $respond = $router->dispatch($request, $respond, false);

        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('hello, world!', $respond->body);

        $request->url = '/test';
        $respond = null;
        $respond = $router->dispatch($request, $respond, false);

        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('test', $respond->body);

        $request->url = '/test2';
        $respond = null;
        $respond = $router->dispatch($request, $respond, false);

        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('test2', $respond->body);

        $request->url = '/test/test';
        $respond = null;
        $respond = $router->dispatch($request, $respond, false);

        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('test3', $respond->body);

        $request->url = '/test4?k=v';
        $respond = null;
        $respond = $router->dispatch($request, $respond, false);

        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('test4', $respond->body);

        $request->url = '/test4/?k=v';
        $respond = null;
        $respond = $router->dispatch($request, $respond, false);

        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('test4', $respond->body);
    }

    public function testRemovePhpFileFromUrl()
    {
        $router = new Router();
        $targetMethod = self::getMethodOfClass('Sleeve\Router', 'RemovePhpFileFromUrl');
        $this->assertEquals(
            '/hello_world?key=1234',
            $targetMethod->invokeArgs($router, array("/www/index.php/hello_world?key=1234",
                "/www/index.php"))
        );
    }
}
