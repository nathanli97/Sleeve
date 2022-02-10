<?php

namespace SimpleRouter;

use SimpleRouter\Exceptions\RespondAlreadySentException;

class Response
{

    public int $status_code;
    public array $headers;
    public array $cookies;
    public string $body;

    protected bool $sent;

    public function __construct()
    {
        $this->status_code = 200;
        $this->headers = array();
        $this->cookies = array();
        $this->sent = false;
        $this->body = '';
    }

    public function convertCookieToHeader(): void
    {
        // TODO
    }

    /**
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

    public function isSent(): bool
    {
        return $this->sent;
    }

    public static function generateFromStatusCode(int $code): Response
    {
        $response = new Response();
        $response->status_code = $code;

        return $response;
    }

    protected function sendHeaders()
    {
        foreach ($this->headers as $key => $value)
        {
            header("$key: $value");
        }
    }

}