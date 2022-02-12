<?php

namespace Sleeve;

use Sleeve\Exceptions\HandlerAlreadyExistException;
use Sleeve\Exceptions\HandlerNotExistsException;
use Sleeve\Exceptions\RespondAlreadySentException;
use Sleeve\Exceptions\UnexpectedCallbackFunctionReturnValue;

/**
 * The Router class.
 * This is the main class of this library.
 * @author nathanli <xingru97@gmail.com>
 * @package Sleeve
 * @license MIT
 */
class Router
{
    /**
     * The route handlers.
     * @var array
     */
    protected array $handlers;

    /**
     * Indicates if a response has been sent.
     * @var bool
     */
    protected bool $sent;

    /**
     * The HTTP Error callback functions.
     * @var array
     */
    protected array $http_error_callbacks;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->initHandlersArray();
        $this->sent = false;
        $this->http_error_callbacks = array();
    }

    /**
     * Add a handler(route) to respond message when receive a http request.
     * For example, Add a route for GET request '/' :
     * $router = new Router();
     * $router->respond('get', '/', function(){return 'hello-world';}); // With HTTP 200 OK.
     *
     * The callback function can return the following types:
     * - Text. (string)
     *    When returning this message, the string you returned will be sent to browser as respond body, with HTTP Status
     * Code 200.
     * - A number.
     *  If 100 <= number <= 599, This number will be treated as HTTP Status Code
     *  Otherwise, it will be sent to browser with HTTP 200 OK after translating to string.
     * - A Object : instance of Response.
     *  The router will use the response directly.
     * @param string $method
     * @param string $regex
     * @param $callback
     * @return void
     */
    public function respond(string $method, string $regex = "", $callback = null)
    {
        $method = strtoupper($method);

        $this->addHandler($method, $regex, $callback);
    }

    /**
     * Dispatch the request.
     * If $request is null, the router will create a request from webserver(e.g. Apache2).
     * If $send_response is true, this response will be sent.
     * @param Request|null $request
     * @param Response|null $response
     * @param bool $send_response
     * @return Response
     * @throws RespondAlreadySentException
     */
    public function dispatch(Request $request = null, Response &$response = null, bool $send_response = true): Response
    {
        if ($request === null) {
            $request = Request::createFromEnvironment();
        }

        if ($response === null) {
            $response = new Response();
        }

        if ($this->sent) {
            throw new RespondAlreadySentException();
        }

        $pending_matches = $this->handlers[$request->method];

        $matches = [];

        $url_without_get_params = $this->removeGetParamFromUrl($request->url);
        if (isset($request->server['SCRIPT_NAME'])) {
            $url_without_get_params = $this->removePhpFileFromUrl(
                $url_without_get_params,
                $request->server['SCRIPT_NAME']
            );
            if (strlen($url_without_get_params) == 0) {
                $url_without_get_params = '/';
            }
        }
        foreach ($pending_matches as $regex => $callback) {
            if (
                preg_match("~$regex~", $url_without_get_params, $preg_matches)
                && strlen($preg_matches[0]) == strlen($url_without_get_params)
            ) {
                $matches[$preg_matches[0]] = $callback;
            }
        }

        if ($request->method == 'HEAD' && sizeof($matches) == 0) {
            foreach ($this->handlers['GET'] as $regex => $callback) {
                if (
                    preg_match("~$regex~", $url_without_get_params, $preg_matches)
                    && strlen($preg_matches[0]) == strlen($url_without_get_params)
                ) {
                    $matches[$preg_matches[0]] = $callback;
                }
            }
        }

        if (sizeof($matches) == 0) {
            $response = Response::generateFromStatusCode(404);

            if (sizeof($this->http_error_callbacks) > 0) {
                foreach ($this->http_error_callbacks as $callback) {
                    $callback($request, $response);
                }
            }

            if (!$this->sent && $send_response) {
                $response->send();
                $this->sent = true;
            }
            return $response;
        }

        $bestMatchCallback = null;
        $bestMatchLength = PHP_INT_MIN;

        foreach ($matches as $match => $callback) {
            $matchLen = strlen($match);
            if ($matchLen > $bestMatchLength) {
                $bestMatchCallback = $callback;
                $bestMatchLength = $matchLen;
            }
        }
        $response_fromCallback = $bestMatchCallback($request);

        if (is_integer($response_fromCallback)) {
            if ($response_fromCallback >= 100 && $response_fromCallback <= 699) {
                $response->status_code = $response_fromCallback;
            } else {
                $response->body = strval($response_fromCallback);
            }
        } elseif (is_string($response_fromCallback)) {
            $response->body = $response_fromCallback;
        } elseif ($response_fromCallback instanceof Response) {
            $response = $response_fromCallback;
        } else {
            throw new UnexpectedCallbackFunctionReturnValue();
        }

        if ($send_response && !$response->isSent()) {
            $response->send();
        }

        $this->sent = $response->isSent();

        return $response;
    }

    /**
     * Removes the given handler.
     * @param string $method
     * @param string $regex
     * @return void
     */
    public function removeHandler(string $method, string $regex = "")
    {
        if (!$this->existsHandler($method, $regex)) {
            throw new HandlerNotExistsException("This handler you try to remove are not existed");
        }

        unset($this->handlers[$method][$regex]);
    }

    /**
     * Indicates the given handler is existed.
     * @param string $method
     * @param string $regex
     * @return bool
     */
    public function existsHandler(string $method, string $regex): bool
    {
        return array_key_exists($regex, $this->handlers[$method]);
    }

    /**
     * Returns how many routes is being defined.
     * @return int
     */
    public function getRouteNum(): int
    {
        $num = 0;
        foreach ($this->handlers as $handler) {
            $num += sizeof($handler);
        }
        return $num;
    }

    /**
     * Add callback function for Http Error Handler.
     * When the router encounters HTTP Error, This Handler will be called.
     * @param $callback
     * @return void
     */
    public function onHttpError($callback)
    {
        $this->http_error_callbacks[] = $callback;
    }

    /**
     * Clear all HTTP Error callback function
     * @return void
     */
    public function clearHttpErrorHandlers(): void
    {
        $this->http_error_callbacks = array();
    }

    /**
     * Initializes the route handlers.
     * @return void
     */
    private function initHandlersArray(): void
    {
        $this->handlers = array();
        $this->handlers['GET'] = array();
        $this->handlers['POST'] = array();
        $this->handlers['HEAD'] = array();
        $this->handlers['OPTION'] = array();
        $this->handlers['PUT'] = array();
        $this->handlers['DELETE'] = array();
    }

    /**
     * Add a route handler.
     * @param string $method
     * @param string $regex
     * @param $callback
     * @return void
     */
    private function addHandler(string $method, string $regex, $callback): void
    {
        if ($this->existsHandler($method, $regex)) {
            throw new HandlerAlreadyExistException("This handler you try to add already existed. \
                                                            Consider remove it before add");
        }

        $this->handlers[$method][$regex] = $callback;
    }

    /**
     * Removes all HTTP GET params from URL.
     * For example:
     * INDEX PHP FILE($file): /www/index.php
     * REQUEST URL($url): /www/index.php/hello_world?key=1234
     * FUNCTION RETURNS: /www/index.php/hello_world
     * @param string $url
     * @return string
     */
    private function removeGetParamFromUrl(string $url): string
    {
        $paramStart = strpos($url, '?');
        if ($paramStart !== false) {
            $str = substr($url, 0, $paramStart);
            if ($str[strlen($str) - 1] == '/' && strlen($str) > 1) {
                $str = substr($str, 0, strlen($str) - 1);
            }
            return $str;
        } else {
            if ($url[strlen($url) - 1] == '/' && strlen($url) > 1) {
                $url = substr($url, 0, strlen($url) - 1);
            }
            return $url;
        }
    }

    /**
     * Removes PHP File name from url.
     * For example:
     * INDEX PHP FILE($file): /www/index.php
     * REQUEST URL($url): /www/index.php/hello_world?key=1234
     * FUNCTION RETURNS: /hello_world?key=1234
     * @param string $url
     * @param string $file
     * @return string
     */
    private function removePhpFileFromUrl(string $url, string $file): string
    {
        if (
            strlen($url) >= strlen($file) &&
            substr($url, 0, strlen($file)) === $file
        ) {
            return substr($url, strlen($file));
        } else {
            return $url;
        }
    }
}
