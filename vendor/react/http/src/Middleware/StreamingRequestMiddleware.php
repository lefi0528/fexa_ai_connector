<?php

namespace React\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;


final class StreamingRequestMiddleware
{
    public function __invoke(ServerRequestInterface $request, $next)
    {
        return $next($request);
    }
}
