<?php

namespace SimpleRouter;

class Response
{
    public int $status_code;
    public array $headers;
    public array $cookies;
    public string $body;

    public function convertCookieToHeader()
    {
    }
}