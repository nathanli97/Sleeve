<?php

namespace SimpleRouter;

class Request
{
    /**
     * Request Method
     * @var string
     */
    public string $method;

    /**
     * Request URL
     * @var string
     */
    public string $url;

    /**
     * Request HTTP Headers
     * @var array
     */
    public array $headers;

    /**
     * Request HTTP GET Params
     * @var array
     */
    public array $get_params;

    /**
     * Request HTTP POST Params
     * @var array
     */
    public array $post_params;

    /**
     * Webserver created attrs
     * @var array
     */
    public array $server;

    /**
     * Uploaded files
     * @var array
     */
    public array $files;

    /**
     * Request body
     * @var string
     */
    public string $body;

    /**
     * Request cookies
     * @var array
     */
    public array $cookies;

    /**
     * Constructor
     * @param string|null $method
     * @param array $headers
     * @param array $get_params
     * @param array $post_params
     * @param array $cookies
     * @param array $server
     * @param array $files
     * @param string|null $body
     */
    public function __construct(
        string $method = 'invalid',
        array $headers = array(),
        array $get_params = array(),
        array $post_params = array(),
        array $cookies = array(),
        array $server = array(),
        array $files = array() ,
        string $body = ''
    )
    {
        $this->method = $method;
        $this->headers = $headers;
        $this->get_params = $get_params;
        $this->post_params = $post_params;
        $this->cookies = $cookies;
        $this->server = $server;
        $this->files = $files;
        $this->body = $body;
    }

    /**
     * Creates Request from current environment(session)
     * @return Request
     */
    public static function createFromEnvironment(): Request
    {
        $request = new Request($_SERVER['REQUEST_METHOD']);
        $request->url = $_SERVER['REQUEST_URI'];
        $request->get_params = $_GET;
        $request->post_params = $_POST;
        $request->files = $_FILES;
        $request->cookies = $_COOKIE;
        $request->server = $_SERVER;
        $request->headers = self::getAllHeaders();
        return $request;
    }

    /**
     * Gets all headers from request
     * @return array
     */
    private static function getAllHeaders(): array
    {
        if(!function_exists('getallheaders'))
        {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
        else
        {
            return getallheaders();
        }
    }

}