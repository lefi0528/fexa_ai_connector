<?php

namespace React\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Io\HttpBodyStream;
use React\Http\Io\PauseBufferStream;
use React\Promise;
use React\Promise\PromiseInterface;
use React\Promise\Deferred;
use React\Stream\ReadableStreamInterface;


final class LimitConcurrentRequestsMiddleware
{
    private $limit;
    private $pending = 0;
    private $queue = array();

    
    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    public function __invoke(ServerRequestInterface $request, $next)
    {
        
        if ($this->pending < $this->limit) {
            ++$this->pending;

            try {
                $response = $next($request);
            } catch (\Exception $e) {
                $this->processQueue();
                throw $e;
            } catch (\Throwable $e) { 
                
                $this->processQueue();
                throw $e; 
            }

            
            
            if ($response instanceof ResponseInterface) {
                $this->processQueue();
                return $response;
            }

            
            
            return $this->await(Promise\resolve($response));
        }

        
        
        $body = $request->getBody();
        if ($body instanceof ReadableStreamInterface) {
            
            $size = $body->getSize();
            $body = new PauseBufferStream($body);
            $body->pauseImplicit();

            
            $request = $request->withBody(new HttpBodyStream(
                $body,
                $size
            ));
        }

        
        $queue =& $this->queue;
        $queue[] = null;
        \end($queue);
        $id = \key($queue);

        $deferred = new Deferred(function ($_, $reject) use (&$queue, $id) {
            
            
            unset($queue[$id]);
            $reject(new \RuntimeException('Cancelled queued next handler'));
        });

        
        $queue[$id] = $deferred;

        $pending = &$this->pending;
        $that = $this;
        return $deferred->promise()->then(function () use ($request, $next, $body, &$pending, $that) {
            
            ++$pending;

            try {
                $response = $next($request);
            } catch (\Exception $e) {
                $that->processQueue();
                throw $e;
            } catch (\Throwable $e) { 
                
                $that->processQueue();
                throw $e; 
            }

            
            if ($body instanceof PauseBufferStream) {
                $body->resumeImplicit();
            }

            
            
            return $that->await(Promise\resolve($response));
        });
    }

    
    public function await(PromiseInterface $promise)
    {
        $that = $this;

        return $promise->then(function ($response) use ($that) {
            $that->processQueue();

            return $response;
        }, function ($error) use ($that) {
            $that->processQueue();

            return Promise\reject($error);
        });
    }

    
    public function processQueue()
    {
        
        if (--$this->pending >= $this->limit || !$this->queue) {
            return;
        }

        $first = \reset($this->queue);
        unset($this->queue[key($this->queue)]);

        $first->resolve(null);
    }
}
