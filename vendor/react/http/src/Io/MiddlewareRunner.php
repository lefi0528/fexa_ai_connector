<?php

namespace React\Http\Io;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;


final class MiddlewareRunner
{
    
    private $middleware;

    
    public function __construct(array $middleware)
    {
        $this->middleware = \array_values($middleware);
    }

    
    public function __invoke(ServerRequestInterface $request)
    {
        if (empty($this->middleware)) {
            throw new \RuntimeException('No middleware to run');
        }

        return $this->call($request, 0);
    }

    
    public function call(ServerRequestInterface $request, $position)
    {
        
        if (!isset($this->middleware[$position + 1])) {
            $handler = $this->middleware[$position];
            return $handler($request);
        }

        $that = $this;
        $next = function (ServerRequestInterface $request) use ($that, $position) {
            return $that->call($request, $position + 1);
        };

        
        $handler = $this->middleware[$position];
        return $handler($request, $next);
    }
}
