<?php

namespace Sleeve\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Sleeve\Exceptions\HandlerAlreadyExistException;
use Sleeve\Exceptions\InvalidEnvironmentException;
use Sleeve\Exceptions\MethodDisabledException;
use Sleeve\Exceptions\RespondAlreadySentException;
use Sleeve\Request;
use Sleeve\Response;
use Sleeve\SleeveRouter;

/**
 * The tests of class SleeveRouter
 */
class RouterTest extends TestCase
{
    /**
     * Returns the method of given class. Used for testing private and protected functions.
     * @param string $cname The class name
     * @param string $name  The method name
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getMethodOfClass(string $cname, string $name): ReflectionMethod
    {
        $class = new ReflectionClass($cname);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Test if the router can add and remove handler normally.
     * @return void
     */
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

    /**
     * Test if the router can not add the same handler twice.
     * @return void
     */
    public function testAddHandlerTwice()
    {
        $this->expectException(HandlerAlreadyExistException::class);
        $router = new SleeveRouter();
        $router->respond("GET");
        $router->respond("GET", "/hello");
        $router->respond("GET", "/hello");
    }

    /**
     * Tests the router can be accessed with an unknown URL, and no exception reported.
     * @return void
     * @throws RespondAlreadySentException
     * @throws InvalidEnvironmentException
     */
    public function testFakeAccess()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $router = new SleeveRouter();
        $respond = $router->dispatch();
        $this->assertEquals(404, $respond->status_code);
        $this->assertEquals(true, $respond->isSent());
    }

    /**
     * Test if the router can not dispatch twice.
     * @return void
     * @throws RespondAlreadySentException
     * @throws InvalidEnvironmentException
     */
    public function testDispatchTwice()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectException(RespondAlreadySentException::class);

        $router = new SleeveRouter();
        $router->dispatch(null, $response);
        $router->dispatch(null, $response);
    }

    /**
     * Test if the router can not dispatch twice.
     * @return void
     * @throws RespondAlreadySentException
     * @throws InvalidEnvironmentException
     */
    public function testDispatchTwiceGlobal()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/fake_access';

        $this->expectException(RespondAlreadySentException::class);

        $router = new SleeveRouter();
        $router->dispatch();
        $router->dispatch();
    }

    /**
     * Tests if router can remove GET param from given URL
     * @return void
     * @throws ReflectionException
     */
    public function testRemoveGetParamFromUrl(): void
    {
        $router = new SleeveRouter();
        $targetMethod = self::getMethodOfClass('Sleeve\SleeveRouter', 'removeGetParamFromUrl');
        $this->assertEquals(
            '/www/index.php/hello_world',
            $targetMethod->invokeArgs($router, array("/www/index.php/hello_world?key=1234"))
        );
    }

    /**
     * Tests the router can remove php file from given URL
     * @return void
     * @throws ReflectionException
     */
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

    /**
     * Tests if the router can access root URL
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     */
    public function testAccessRoot()
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

    /**
     * Tests if the router can be accessed with difference URL
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     */
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

    /**
     * Tests the router should fall back to GET method when trying to access a non-existed HEAD route.
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     */
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

    /**
     * Tests the router can handle unknown method request.
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     */
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

    /**
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     * @throws MethodDisabledException
     */
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

    /**
     * Test the HTTPError callback is working normally.
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     */
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

    /**
     * Tests the router can clear all the http error handler callbacks.
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     */
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

    /**
     * Test the router can work normally in subdirectory.
     * @return void
     * @throws InvalidEnvironmentException
     * @throws RespondAlreadySentException
     */
    public function testSubdirectoryRouting()
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
