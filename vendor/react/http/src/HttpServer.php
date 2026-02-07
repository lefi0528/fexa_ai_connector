<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Io\IniUtil;
use React\Http\Io\MiddlewareRunner;
use React\Http\Io\StreamingServer;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Socket\ServerInterface;


final class HttpServer extends EventEmitter
{
    
    const MAXIMUM_BUFFER_SIZE = 65536; 

    
    private $streamingServer;

    
    public function __construct($requestHandlerOrLoop)
    {
        $requestHandlers = \func_get_args();
        if (reset($requestHandlers) instanceof LoopInterface) {
            $loop = \array_shift($requestHandlers);
        } else {
            $loop = Loop::get();
        }

        $requestHandlersCount = \count($requestHandlers);
        if ($requestHandlersCount === 0 || \count(\array_filter($requestHandlers, 'is_callable')) < $requestHandlersCount) {
            throw new \InvalidArgumentException('Invalid request handler given');
        }

        $streaming = false;
        foreach ((array) $requestHandlers as $handler) {
            if ($handler instanceof StreamingRequestMiddleware) {
                $streaming = true;
                break;
            }
        }

        $middleware = array();
        if (!$streaming) {
            $maxSize = $this->getMaxRequestSize();
            $concurrency = $this->getConcurrentRequestsLimit(\ini_get('memory_limit'), $maxSize);
            if ($concurrency !== null) {
                $middleware[] = new LimitConcurrentRequestsMiddleware($concurrency);
            }
            $middleware[] = new RequestBodyBufferMiddleware($maxSize);
            
            
            
            
            
            $enablePostDataReading = \ini_get('enable_post_data_reading');
            if ($enablePostDataReading !== '') {
                $middleware[] = new RequestBodyParserMiddleware();
            }
        }

        $middleware = \array_merge($middleware, $requestHandlers);

        
        $middleware = \array_filter($middleware, function ($handler) {
            return !($handler instanceof StreamingRequestMiddleware);
        });

        $this->streamingServer = new StreamingServer($loop, new MiddlewareRunner($middleware));

        $that = $this;
        $this->streamingServer->on('error', function ($error) use ($that) {
            $that->emit('error', array($error));
        });
    }

    
    public function listen(ServerInterface $socket)
    {
        $this->streamingServer->listen($socket);
    }

    
    private function getConcurrentRequestsLimit($memory_limit, $post_max_size)
    {
        if ($memory_limit == -1) {
            return null;
        }

        $availableMemory = IniUtil::iniSizeToBytes($memory_limit) / 2;
        $concurrentRequests = (int) \ceil($availableMemory / IniUtil::iniSizeToBytes($post_max_size));

        return $concurrentRequests;
    }

    
    private function getMaxRequestSize($post_max_size = null)
    {
        $maxSize = IniUtil::iniSizeToBytes($post_max_size === null ? \ini_get('post_max_size') : $post_max_size);

        return ($maxSize === 0 || $maxSize >= self::MAXIMUM_BUFFER_SIZE) ? self::MAXIMUM_BUFFER_SIZE : $maxSize;
    }
}
