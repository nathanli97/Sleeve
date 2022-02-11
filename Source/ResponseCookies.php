<?php

namespace SimpleRouter;

class ResponseCookies
{
    protected array $names;
    protected array $values;
    protected array $expires;
    protected array $paths;
    protected array $domains;
    protected array $secures;
    protected array $httponly;

    public function __construct()
    {
        //$this->cookies = array();
    }

    public function addCookie(string $name,
                              string $value = "",
                              int $expires = 0,
                              string $path = "",
                              string $domain = "",
                              bool $secure = false,
                              bool $httponly = false)
    {
        $this->names[] = $name;
        $this->values[] = $value;
        $this->expires[] = $expires;
        $this->paths[] = $path;
        $this->domains[] = $domain;
        $this->secures[] = $secure;
        $this->httponly[] = $httponly;
    }

    public function setCookies()
    {
        $num = sizeof($this->names);
        for($i = 0; $i < $num; $i++)
        {
            setcookie(
                $this->names[$i],
                $this->values[$i],
                $this->expires[$i],
                $this->paths[$i],
                $this->domains[$i],
                $this->secures[$i],
                $this->httponly[$i]
            );
        }
    }

    public function hasCookies()
    {
        return sizeof($this->names) > 0;
    }
}