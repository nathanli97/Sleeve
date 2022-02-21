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
 * PHP Version 7.4
 *
 * @category SleeveRouter
 * @package  Sleeve
 * @author   nathanli <xingru97@gmail.com>
 * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/nathanli/Sleeve
 */

namespace Sleeve;

use Sleeve\Exceptions\InvalidEnvironmentException;

/**
 * The HTTP Request wrapper class
 *
 * @category SleeveRouter
 * @package  Sleeve
 * @author   nathanli <xingru97@gmail.com>
 * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/nathanli/Sleeve
 */

class Request
{
    /**
     * Request Method
     *
     * @var string
     */
    public string $method;

    /**
     * Request URL
     *
     * @var string
     */
    public string $url;

    /**
     * Request HTTP Headers
     *
     * @var array
     */
    public array $headers;

    /**
     * Request HTTP GET Params
     *
     * @var array
     */
    public array $get_params;

    /**
     * Request HTTP POST Params
     *
     * @var array
     */
    public array $post_params;

    /**
     * Webserver created attrs
     *
     * @var array
     */
    public array $server;

    /**
     * Uploaded files
     *
     * @var array
     */
    public array $files;

    /**
     * Request body
     *
     * @var string
     */
    public string $body;

    /**
     * Request cookies
     *
     * @var array
     */
    public array $cookies;

    /**
     * Constructor
     *
     * @param string|null $method      HTTP Request method.
     * @param array       $headers     HTTP Headers.
     * @param array       $get_params  HTTP GET Params.
     * @param array       $post_params HTTP POST Params.
     * @param array       $cookies     HTTP Cookies.
     * @param array       $server      Server variables.Set by WebServer.
     * @param array       $files       HTTP Uploaded files.
     * @param string|null $body        HTTP Request Body.
     */
    public function __construct(
        string $method = 'GET',
        array $headers = array(),
        array $get_params = array(),
        array $post_params = array(),
        array $cookies = array(),
        array $server = array(),
        array $files = array(),
        string $body = ''
    ) {
        $this->method = strtoupper($method);
        $this->headers = $headers;
        $this->get_params = $get_params;
        $this->post_params = $post_params;
        $this->cookies = $cookies;
        $this->server = $server;
        $this->files = $files;

        // Take care the default value
        if ($body !== '') {
            $this->body = $body;
        }
    }

    /**
     * Return true if it has cookie named $name
     * @param string $name The cookie key name
     * @return bool
     */
    public function hasCookie(string $name): bool
    {
        return sizeof($this->cookies) > 0 && array_key_exists($name, $this->cookies);
    }

    /**
     * Get HTTP Cookie named $name
     * @param string $name The cookie key name
     * @return string
     */
    public function getCookie(string $name): string
    {
        return $this->cookies[$name];
    }

    /**
     * Get count of cookies
     * @return int
     */
    public function getCookieNum(): int
    {
        return count($this->cookies);
    }

    /**
     * Return true if it has HTTP-GET param named $name
     * @param string $name The GET param key name
     * @return bool
     */
    public function hasGetParam(string $name): bool
    {
        return count($this->get_params) > 0 && array_key_exists($name, $this->get_params);
    }

    /**
     * Get HTTP-GET Param named $name
     * @param string $name The GET param key name
     * @return mixed
     */
    public function getGetParam(string $name)
    {
        return $this->get_params[$name];
    }

    /**
     * Get count of HTTP-GET Params
     * @return int
     */
    public function getGetParamNum(): int
    {
        return sizeof($this->get_params);
    }

    /**
     * Return true if it has POST param named $name
     * @param string $name The POST param key name
     * @return bool
     */
    public function hasPostParam(string $name): bool
    {
        return sizeof($this->post_params) > 0 && array_key_exists($name, $this->post_params);
    }

    /**
     * Get HTTP-POST Param named $name
     *
     * @param string $name The POST param key name
     * @return mixed
     */
    public function getPostParam(string $name)
    {
        return $this->post_params[$name];
    }

    /**
     * Get count of POST Params
     * @return int
     */
    public function getPostParamNum(): int
    {
        return sizeof($this->post_params);
    }

    /**
     * Return true if it has Uploaded File named $name
     * @param string $name The uploaded file name
     * @return bool
     */
    public function hasFile(string $name): bool
    {
        return sizeof($this->files) > 0 && array_key_exists($name, $this->files);
    }

    /**
     * Get uploaded file named $name
     *
     * If PHP Version is below 8.1.0, the RequestFile.full_path will be null.
     * @see RequestFile
     * @param string $name The uploaded file name
     * @return RequestFile
     */
    public function getFile(string $name): RequestFile
    {
        return new RequestFile(
            $this->files[$name]['name'],
            $this->files[$name]['type'],
            $this->files[$name]['size'],
            $this->files[$name]['tmp_name'],
            $this->files[$name]['error'],
            // full_path available since PHP 8.1.0
            array_key_exists('full_path', $this->files[$name]) ? $this->files[$name] : null
        );
    }

    /**
     * Get count of uploaded files
     * @return int
     */
    public function getFileNum(): int
    {
        return count($this->files);
    }

    /**
     * Return true if it has HTTP Header named $name
     * @param string $name The HTTP Header key name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return count($this->headers) > 0 && array_key_exists($name, $this->headers);
    }

    /**
     * Get HTTP Header named $name
     * @param string $name The HTTP Header key name
     * @return string
     */
    public function getHeader(string $name): string
    {
        return $this->headers[$name];
    }

    /**
     * Get count of HTTP Headers
     * @return int
     */
    public function getHeaderNum()
    {
        return sizeof($this->headers);
    }

    /**
     * Creates Request from current environment(session)
     *
     * @return Request
     * @throws InvalidEnvironmentException
     */
    public static function createFromEnvironment(): Request
    {
        if (!array_key_exists('REQUEST_METHOD', $_SERVER) || !array_key_exists('REQUEST_URI', $_SERVER)) {
            throw new InvalidEnvironmentException();
        }
        $request = new Request($_SERVER['REQUEST_METHOD']);
        $request->url = $_SERVER['REQUEST_URI'];
        $request->get_params = $_GET;
        $request->post_params = $_POST;
        $request->files = $_FILES;
        $request->cookies = $_COOKIE;
        $request->server = $_SERVER;
        $request->headers = self::getAllHeaders();
        $request->body = @file_get_contents('php://input');
        return $request;
    }

    /**
     * Gets all HTTP headers from request
     *
     * @return array
     */
    private static function getAllHeaders(): array
    {
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    )] = $value;
                }
            }
            return $headers;
        } else {
            return getallheaders();
        }
    }
}
