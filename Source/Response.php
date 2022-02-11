<?php

namespace SimpleRouter;

use SimpleRouter\Exceptions\RespondAlreadySentException;

/**
 * The Response class
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
     * @var array
     */
    public array $cookies;

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
        $this->status_code = 200;
        $this->headers = array();
        $this->cookies = array();
        $this->sent = false;
        $this->body = '';
    }

    /**
     * Converts the cookies to HTTP Header to prepare for sent
     * @return void
     */
    public function convertCookieToHeader(): void
    {
        // TODO
    }

    /**
     * Send this response
     * @throws RespondAlreadySentException
     */
    public function send()
    {
        if($this->sent)
        {
            throw new RespondAlreadySentException();
        }
        $this->convertCookieToHeader();
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
    protected function sendHeaders()
    {
        foreach ($this->headers as $key => $value)
        {
            header("$key: $value");
        }
    }

}