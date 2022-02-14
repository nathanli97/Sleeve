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
 * @category Router
 * @package  Sleeve
 * @author   nathanli <xingru97@gmail.com>
 * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/nathanli/Sleeve
 */

namespace Sleeve;

use Sleeve\Exceptions\RespondAlreadySentException;

/**
 * The Response class
 * @author nathanli <xingru97@gmail.com>
 * @package Sleeve
 * @license Apache2
 */
class Response
{
    /**
     * The HTTP Status Code
     * @var int
     */
    public int $status_code;

    /**
     * The HTTP Headers
     * @var array
     */
    public array $headers;

    /**
     * The HTTP Cookies
     * @var ResponseCookies
     */
    public ResponseCookies $cookies;

    /**
     * The Response message body
     * @var string
     */
    public string $body;

    /**
     * Indicates this response has been sent
     * @var bool
     */
    protected bool $sent;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->clear();
    }

    /**
     * Clear out this (old) response to prepare for new response.
     * @return void
     */
    public function clear(): void
    {
        $this->status_code = 200;
        $this->headers = array();
        $this->cookies = new ResponseCookies();
        $this->sent = false;
        $this->body = '';
    }
    /**
     * Send this response
     * @throws RespondAlreadySentException
     */
    public function send(): void
    {
        if ($this->sent) {
            throw new RespondAlreadySentException();
        }

        if ($this->cookies->hasCookies()) {
            $this->cookies->setCookies();
        }

        http_response_code($this->status_code);
        $this->sendHeaders();
        print $this->body;
        $this->sent = true;
    }

    /**
     * If return true, This response has been sent
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Generates a response from specified status code
     * @param int $code
     * @return Response
     */
    public static function generateFromStatusCode(int $code): Response
    {
        $response = new Response();
        $response->status_code = $code;

        return $response;
    }

    /**
     * Used for set headers
     * @return void
     */
    protected function sendHeaders(): void
    {
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
    }
}
