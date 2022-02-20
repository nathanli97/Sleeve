<?php

/**
 * Copyright [2022] [nathanli]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 *
 *
 * PHP Version 7.4
 *
 * @category SleeveRouter
 * @package  Sleeve
 * @author   nathanli <xingru97@gmail.com>
 * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/nathanli/Sleeve
 */

namespace Sleeve;

use Sleeve\Exceptions\HandlerAlreadyExistException;
use Sleeve\Exceptions\HandlerNotExistsException;
use Sleeve\Exceptions\InvalidEnvironmentException;
use Sleeve\Exceptions\MethodDisabledException;
use Sleeve\Exceptions\RespondAlreadySentException;
use Sleeve\Traits\Callback;

/**
 * The SleeveRouter class.
 * This is the main class of this library.
 * @author nathanli <xingru97@gmail.com>
 * @package Sleeve
 * @license Apache2
 */
class SleeveRouter
{
    use Callback;

    /**
     * The route handlers.
     * @var array
     */
    protected array $handlers;

    /**
     * Indicates if a response has been sent.
     * @var bool
     */
    protected bool $has_response_sent;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->initHandlersArray();
        $this->initCallbacks();
        $this->has_response_sent = false;
    }

    /**
     * Method handlers
     * @param string $regex
     * @param $callback
     * @return void
     */
    public function get(string $regex = "", $callback = null)
    {
        $this->respond('get', $regex, $callback);
    }
    public function options(string $regex = "", $callback = null)
    {
        $this->respond('options', $regex, $callback);
    }
    public function post(string $regex = "", $callback = null)
    {
        $this->respond('post', $regex, $callback);
    }
    public function head(string $regex = "", $callback = null)
    {
        $this->respond('head', $regex, $callback);
    }
    public function put(string $regex = "", $callback = null)
    {
        $this->respond('put', $regex, $callback);
    }
    public function delete(string $regex = "", $callback = null)
    {
        $this->respond('delete', $regex, $callback);
    }
    public function trace(string $regex = "", $callback = null)
    {
        $this->respond('trace', $regex, $callback);
    }
    public function connect(string $regex = "", $callback = null)
    {
        $this->respond('connect', $regex, $callback);
    }

    /**
     * Add a handler(route) to respond message when receive a http request.
     * For example, Add a route for GET request '/' :
     * $router = new SleeveRouter();
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
     * @throws RespondAlreadySentException|InvalidEnvironmentException
     */
    public function dispatch(Request $request = null, Response &$response = null, bool $send_response = true): Response
    {
        if ($request === null) {
            $request = Request::createFromEnvironment();
        }

        $request->method = strtoupper($request->method);

        if ($response === null) {
            $response = new Response();
        }

        if ($this->has_response_sent) {
            throw new RespondAlreadySentException();
        }

        if (
            !in_array($request->method, array(
                'OPTIONS',
                'GET',
                'HEAD',
                'POST',
                'PUT',
                'DELETE',
                'TRACE',
                'CONNECT'
            ))
        ) {
            // Unknown HTTP Method, generates 501 Not Implemented Response
            $response = Response::generateFromStatusCode(501);
            $response = $this->processCallbackReturnValue(
                $this->callCallback($this->unimplemented_method_access_callbacks, array($request, $response)),
                $request,
                $response
            );
            $response = $this->processCallbackReturnValue(
                $this->callCallback($this->http_error_callbacks, array($request, $response)),
                $request,
                $response
            );
            if ($send_response) {
                $response->send();
                $this->has_response_sent = $response->isSent();
            }
            return $response;
        }
        if (!array_key_exists($request->method, $this->handlers)) {
            // This HTTP Method already disabled.
            $response = Response::generateFromStatusCode(405);
            $response = $this->processCallbackReturnValue(
                $this->callCallback($this->disabled_method_access_callbacks, array($request, $response)),
                $request,
                $response
            );
            $response = $this->processCallbackReturnValue(
                $this->callCallback($this->http_error_callbacks, array($request, $response)),
                $request,
                $response
            );
            if ($send_response) {
                $response->send();
                $this->has_response_sent = $response->isSent();
            }
            return $response;
        }

        $pending_matches = $this->handlers[strtoupper($request->method)];

        $matches = [];

        $url_without_get_params = $this->removeGetParamFromUrl($request->url);
        if (isset($request->server['SCRIPT_NAME'])) {
            $url_without_get_params = $this->removePhpFileFromUrl(
                $url_without_get_params,
                $request->server['SCRIPT_NAME']
            );
            $url_without_get_params = $this->removeSubDirFromUrl(
                $url_without_get_params,
                $request->server['SCRIPT_NAME']
            );
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
                    $response = $response = $this->processCallbackReturnValue(
                        $callback($request, $response),
                        $request,
                        $response
                    );
                }
            }

            if (!$this->has_response_sent && $send_response) {
                $response->send();
                $this->has_response_sent = true;
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
        $response_fromCallback = $bestMatchCallback($request, $response);

        $response = $this->processCallbackReturnValue($response_fromCallback, $request, $response);

        if ($send_response && !$response->isSent()) {
            $response->send();
        }

        $this->has_response_sent = $response->isSent();

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
     * Disables the specified HTTP Method
     * @param string $method The Method Name.
     * @return void
     * @throws MethodDisabledException When trying to disable an already disabled HTTP Method, throws this Exception
     */
    public function disableMethod(string $method): void
    {
        $method = strtoupper($method);
        if (array_key_exists($method, $this->handlers)) {
            unset($this->handlers[$method]);
        } else {
            throw new MethodDisabledException();
        }
    }

    /**
     * Initializes the route handlers.
     * @return void
     */
    private function initHandlersArray(): void
    {
        $this->handlers = array();
        $this->handlers['GET'] = array();
        $this->handlers['CONNECT'] = array();
        $this->handlers['POST'] = array();
        $this->handlers['HEAD'] = array();
        $this->handlers['OPTIONS'] = array();
        $this->handlers['PUT'] = array();
        $this->handlers['DELETE'] = array();
        $this->handlers['TRACE'] = array();
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
            $url = substr($url, strlen($file));
        }

        if (strlen($url) == 0) {
            $url = '/';
        }
        return $url;
    }
    /**
     * Removes sub dir from url.
     * For example:
     * INDEX PHP FILE($file): /abc/def/public/index.php
     * REQUEST URL($url): /abc/def/public/version
     * FUNCTION RETURNS: /version
     * @param string $url
     * @param string $file
     * @return string
     */
    private function removeSubDirFromUrl(string $url, string $file): string
    {
        $fileLength = strlen($file);
        if ($fileLength >= 4 && substr($file, $fileLength - 4) == '.php') {
            $file = preg_replace('~/[^/]*.php$~', "", $file);
        }
        $url = preg_replace("~$file~", "", $url);
        if (strlen($url) == 0) {
            $url = '/';
        }
        return $url;
    }
}
