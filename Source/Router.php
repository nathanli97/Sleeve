<?php

namespace SimpleRouter;


use SimpleRouter\Exceptions\HandlerAlreadyExistException;
use SimpleRouter\Exceptions\HandlerNotExistsException;
use SimpleRouter\Exceptions\RespondAlreadySentException;
use SimpleRouter\Exceptions\UnexceptedCallbackFuntionReturnValue;

class Router
{

    public function __construct()
    {
        $this->initHandlersArray();
        $this->sent = false;
        $this->http_error_callbacks = array();
    }
    public function respond(string $method, string $regex = "", $callback = null)
    {
        $method = strtoupper($method);

        $this->addHandler($method, $regex, $callback);
    }

    /**
     * @throws Exceptions\RespondAlreadySentException
     */
    public function dispatch(Request $request = null, Response &$response = null, bool $send_response = true): Response
    {
        if($request === null)
        {
            $request = Request::createFromEnvironment();
        }

        if($response === null)
        {
            $response = new Response();
        }

        if($this->sent)
        {
            throw new RespondAlreadySentException();
        }

        $pending_matches = $this->handlers[$request->method];

        $matches = [];

        $url_without_get_params = $this->removeGetParamFromUrl($request->url);

        foreach ($pending_matches as $regex => $callback)
        {
            if(preg_match("~$regex~", $url_without_get_params))
            {
                $matches[$regex] = $callback;
            }
        }

        if($request->method == 'HEAD' && sizeof($matches) == 0)
        {
            foreach ($this->handlers['GET'] as $regex => $callback)
            {
                if(preg_match($regex, $url_without_get_params))
                {
                    $matches[$regex] = $callback;
                }
            }
        }

        if(sizeof($matches) == 0)
        {
            $response = Response::generateFromStatusCode(404);

            if(sizeof($this->http_error_callbacks) > 0)
            {
                foreach ($this->http_error_callbacks as $callback)
                {
                    $callback($request, $response);
                }
            }

            if(!$this->sent && $send_response)
            {
                $response->send();
                $this->sent = true;
            }
            return $response;
        }

        //$bestMatchRegex = null;
        $bestMatchCallback = null;
        $bestMatchLength = PHP_INT_MIN;

        foreach ($matches as $regex => $callback)
        {
            $matchLen = strlen($regex);
            if($matchLen > $bestMatchLength)
            {
                //$bestMatchRegex = $regex;
                $bestMatchCallback = $callback;
                $bestMatchLength = $matchLen;
            }
        }
        // TODO - Set params for request obj
        $response_fromCallback = $bestMatchCallback($request);

        if(is_integer($response_fromCallback))
        {
            if($response_fromCallback >= 100 && $response_fromCallback <= 699)
            {
                $response->status_code = $response_fromCallback;
            }
            else
            {
                $response->body = strval($response_fromCallback);
            }
        }
        else if (is_string($response_fromCallback))
        {
            $response->body = $response_fromCallback;
        }
        else if ($response_fromCallback instanceof Response)
        {
            $response = $response_fromCallback;
        }
        else
            throw new UnexceptedCallbackFuntionReturnValue();

        if($send_response && !$response->isSent())
        {
            $response->send();
        }

        $this->sent = $response->isSent();

        return $response;
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

    public function onHttpError($callback)
    {
        $this->http_error_callbacks[] = $callback;
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

    private function removeGetParamFromUrl(string $url): string
    {
        $paramStart = strpos($url, '?');
        if($paramStart !== false)
        {
            $str = substr($url,0,$paramStart);
            if($str[strlen($str) - 1] == '/' && strlen($str) > 1)
            {
                $str = substr($str,0,strlen($str) - 1);
            }
            return $str;
        }
        else
        {
            if($url[strlen($url) - 1] == '/' && strlen($url) > 1)
            {
                $url = substr($url,0,strlen($url) - 1);
            }
            return $url;
        }
    }

    protected array $handlers;
    protected bool $sent;
    protected array $http_error_callbacks;
}