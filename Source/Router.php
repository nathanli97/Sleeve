<?php

namespace SimpleRouter;


use SimpleRouter\Exceptions\HandlerAlreadyExistException;
use SimpleRouter\Exceptions\HandlerNotExistsException;

class Router
{
    public function __construct()
    {
        $this->initHandlersArray();
    }
    public function respond(string $method, string $regex = "", $callback = null)
    {
        $method = strtoupper($method);

        $this->addHandler($method, $regex, $callback);
    }

    public function dispath(Request $request = null, Response $response = null, bool $send_response = true)
    {
        if($request === null)
        {
            $request = Request::createFromEnvironment();
        }

        if($response === null)
        {
            $response = new Response();
        }


    }

    public function removeHandler(string $method, string $regex = "")
    {
        if(!$this->existsHandler($method, $regex))
        {
            throw new HandlerNotExistsException("This handler you try to remove are not existed");
        }

        unset($this->handlers[$method][$regex]);
    }

    public function existsHandler(string $method, string $regex): bool
    {
        return array_key_exists($regex, $this->handlers[$method]);
    }

    public function getRouteNum(): int
    {
        $num = 0;
        foreach ($this->handlers as $handler)
        {
            $num += sizeof($handler);
        }
        return $num;
    }

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

    private function addHandler(string $method, string $regex, $callback): void
    {
        if($this->existsHandler($method, $regex))
        {
            throw new HandlerAlreadyExistException("This handler you try to add already existed. \
                                                            Consider remove it before add");
        }

        $this->handlers[$method][$regex] = $callback;
    }

    protected array $handlers;
}