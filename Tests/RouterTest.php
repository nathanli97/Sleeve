<?php

namespace Sleeve\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sleeve\Exceptions\HandlerAlreadyExistException;
use Sleeve\Exceptions\RespondAlreadySentException;
use Sleeve\Request;
use Sleeve\Response;
use Sleeve\SleeveRouter;

class RouterTest extends TestCase
{
    protected static function getMethodOfClass($cname, $name): \ReflectionMethod
    {
        $class = new ReflectionClass($cname);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testAddRemoveHandler()
    {
        $router = new SleeveRouter();
        $router->respond("GET");
        $router->respond("GET", "/hello");
        $router->respond("POST", "/hello");
        $router->respond("PUT", "/hello");
        $router->respond("DELETE", "/hello");
        $router->respond("HEAD", "/hello");
        $router->respond("OPTIONS", "/hello");
        $router->respond("GET", "/world");
        $router->respond("POST", "/world");
        $router->removeHandler("GET", "/hello");
        $router->respond("GET", "/hello");
        $this->assertEquals(9, $router->getRouteNum());
    }
    public function testAddHandlerTwice()
    {
        $this->expectException(HandlerAlreadyExistException::class);
        $router = new SleeveRouter();
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
        $router = new SleeveRouter();
        $router->dispatch();
    }

    public function testDispatchTwice()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectException(RespondAlreadySentException::class);

        $router = new SleeveRouter();
        $router->dispatch(null, $response);
        $router->dispatch(null, $response);
    }

    public function testDispatchTwiceGlobal()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectException(RespondAlreadySentException::class);

        $router = new SleeveRouter();
        $router->dispatch();
        $router->dispatch();
    }

    public function testRemoveGetParamFromUrl()
    {
        $router = new SleeveRouter();
        $targetMethod = self::getMethodOfClass('Sleeve\SleeveRouter', 'removeGetParamFromUrl');
        $this->assertEquals(
            '/www/index.php/hello_world',
            $targetMethod->invokeArgs($router, array("/www/index.php/hello_world?key=1234"))
        );
    }

    public function testAccess404()
    {
        $router = new SleeveRouter();
        $respond = $router->dispatch(null, $respond, false);

        $this->assertEquals(404, $respond->status_code);
    }

    public function testAccess404Sent()
    {
        $router = new SleeveRouter();
        $respond = $router->dispatch(null, $respond);

        $this->assertEquals(404, $respond->status_code);
        $this->assertEquals(true, $respond->isSent());
    }

    public function testAccessIndex()
    {
        $router = new SleeveRouter();
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
        $router = new SleeveRouter();
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
        $router = new SleeveRouter();
        $targetMethod = self::getMethodOfClass('Sleeve\SleeveRouter', 'RemovePhpFileFromUrl');
        $this->assertEquals(
            '/hello_world?key=1234',
            $targetMethod->invokeArgs($router, array("/www/index.php/hello_world?key=1234",
                "/www/index.php"))
        );
    }

    public function testHeadFallbackToGet()
    {
        $router = new SleeveRouter();
        $router->respond('get', '/get_only', function () {
            return 'This is GET-ONLY';
        });
        $router->respond('head', '/head_only', function () {
            return 'This is HEAD-ONLY';
        });

        $request = new Request('get');
        $request->url = '/get_only';
        $respond = $router->dispatch($request, $respond, false);
        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('This is GET-ONLY', $respond->body);

        $request->method = 'head';
        $request->url = '/head_only';
        $respond = $router->dispatch($request, $respond, false);
        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('This is HEAD-ONLY', $respond->body);

        $request->url = '/get_only';
        $respond = $router->dispatch($request, $respond, false);
        $this->assertEquals(200, $respond->status_code);
        $this->assertEquals('This is GET-ONLY', $respond->body);
    }

    public function testUnknownMethodRequest()
    {
        $router = new SleeveRouter();
        $request = new Request('EMMM');
        $request->url = '/';
        $router->onUnimplementedMethod(function ($request, Response $response) {
            $response->body = 'This method is unimplemented';
            return $response;
        });
        $router->onDisabledMethod(function ($request, Response $response) {
            $response->body = 'This method is disabled';
            return $response;
        });
        $respond = $router->dispatch($request, $respond, false);
        $this->assertEquals(501, $respond->status_code);
        $this->assertEquals('This method is unimplemented', $respond->body);
    }

    public function testHttpErrorCallback()
    {
        $callback = function (Request $request, Response $response) {
            $response->body = 'hey!';
            return $response;
        };
        $router = new SleeveRouter();
        $router->onHttpError($callback);
        $request = new Request('get');
        $request->url = '/';
        $response = $router->dispatch($request, $response, false);
        $this->assertEquals(404, $response->status_code);
        $this->assertEquals('hey!', $response->body);
    }

    public function testClearHttpErrorHandlers()
    {
        $callback = function (Request $request, Response $response) {
            $response->body = 'hey!';
            return $response;
        };
        $router = new SleeveRouter();
        $router->onHttpError($callback);
        $request = new Request('get');
        $request->url = '/';
        $router->clearHttpErrorCallbacks();
        $response = $router->dispatch($request, $response, false);
        $this->assertEquals(404, $response->status_code);
        $this->assertEquals('', $response->body);
    }

    public function testDisableMethod()
    {
        $router = new SleeveRouter();

        $router->onUnimplementedMethod(function ($request, Response $response) {
            $response->body = 'This method is unimplemented';
            return $response;
        });
        $router->onDisabledMethod(function ($request, Response $response) {
            $response->body = 'This method is disabled';
            return $response;
        });

        $router->disableMethod('get');
        $request = new Request('get');
        $request->url = '/';
        $response = $router->dispatch($request, $response, false);
        $this->assertEquals(405, $response->status_code);
        $this->assertEquals('This method is disabled', $response->body);

        $request->method = 'post';
        $request->url = '/';
        $response = $router->dispatch($request, $response, false);
        $this->assertEquals(404, $response->status_code);

        $router->respond('post', '/', function () {
            return 'post';
        });

        $response->clear();
        $response = $router->dispatch($request, $response, false);
        $this->assertEquals(200, $response->status_code);
        $this->assertEquals('post', $response->body);
    }

    public function testSubdirRouting()
    {
        $router = new SleeveRouter();
        $request = new Request('get');
        $request->url = '/abc/def/public/version';
        $request->server['SCRIPT_NAME'] = '/abc/def/public/index.php';
        $router->get('/version', function (Request $request, Response $response) {
            return 'version!';
        });
        $response = $router->dispatch($request, $response, false);
        $this->assertEquals(200, $response->status_code);
        $this->assertEquals('version!', $response->body);
    }
}
