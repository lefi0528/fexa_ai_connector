<?php

namespace React\Http\Io;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Http\Client\Client as HttpClient;
use React\Promise\PromiseInterface;
use React\Promise\Deferred;
use React\Socket\ConnectorInterface;
use React\Stream\ReadableStreamInterface;


class Sender
{
    
    public static function createFromLoop(LoopInterface $loop, ConnectorInterface $connector)
    {
        return new self(new HttpClient(new ClientConnectionManager($connector, $loop)));
    }

    private $http;

    
    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    
    public function send(RequestInterface $request)
    {
        
        assert(\in_array($request->getProtocolVersion(), array('1.0', '1.1'), true));

        $body = $request->getBody();
        $size = $body->getSize();

        if ($size !== null && $size !== 0) {
            
            $request = $request->withHeader('Content-Length', (string)$size);
        } elseif ($size === 0 && \in_array($request->getMethod(), array('POST', 'PUT', 'PATCH'))) {
            
            $request = $request->withHeader('Content-Length', '0');
        } elseif ($body instanceof ReadableStreamInterface && $size !== 0 && $body->isReadable() && !$request->hasHeader('Content-Length')) {
            
            $request = $request->withHeader('Transfer-Encoding', 'chunked');
        } else {
            
            $size = 0;
        }

        
        if ($request->getUri()->getUserInfo() !== '' && !$request->hasHeader('Authorization')) {
            $request = $request->withHeader('Authorization', 'Basic ' . \base64_encode($request->getUri()->getUserInfo()));
        }

        $requestStream = $this->http->request($request);

        $deferred = new Deferred(function ($_, $reject) use ($requestStream) {
            
            $reject(new \RuntimeException('Request cancelled'));
            $requestStream->close();
        });

        $requestStream->on('error', function($error) use ($deferred) {
            $deferred->reject($error);
        });

        $requestStream->on('response', function (ResponseInterface $response) use ($deferred, $request) {
            $deferred->resolve($response);
        });

        if ($body instanceof ReadableStreamInterface) {
            if ($body->isReadable()) {
                
                if ($size === null) {
                    $body = new ChunkedEncoder($body);
                }

                
                
                $body->pipe($requestStream);
                $requestStream->write('');

                $body->on('close', $close = function () use ($deferred, $requestStream) {
                    $deferred->reject(new \RuntimeException('Request failed because request body closed unexpectedly'));
                    $requestStream->close();
                });
                $body->on('error', function ($e) use ($deferred, $requestStream, $close, $body) {
                    $body->removeListener('close', $close);
                    $deferred->reject(new \RuntimeException('Request failed because request body reported an error', 0, $e));
                    $requestStream->close();
                });
                $body->on('end', function () use ($close, $body) {
                    $body->removeListener('close', $close);
                });
            } else {
                
                $requestStream->end();
            }
        } else {
            
            $requestStream->end((string)$body);
        }

        return $deferred->promise();
    }
}
