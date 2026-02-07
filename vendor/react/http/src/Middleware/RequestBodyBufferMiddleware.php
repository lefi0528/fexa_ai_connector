<?php

namespace React\Http\Middleware;

use OverflowException;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Io\BufferedBody;
use React\Http\Io\IniUtil;
use React\Promise\Promise;
use React\Stream\ReadableStreamInterface;

final class RequestBodyBufferMiddleware
{
    private $sizeLimit;

    
    public function __construct($sizeLimit = null)
    {
        if ($sizeLimit === null) {
            $sizeLimit = \ini_get('post_max_size');
        }

        $this->sizeLimit = IniUtil::iniSizeToBytes($sizeLimit);
    }

    public function __invoke(ServerRequestInterface $request, $next)
    {
        $body = $request->getBody();
        $size = $body->getSize();

        
        if ($size === 0 || !$body instanceof ReadableStreamInterface || !$body->isReadable()) {
            
            if ($body instanceof ReadableStreamInterface || $size > $this->sizeLimit) {
                $request = $request->withBody(new BufferedBody(''));
            }

            return $next($request);
        }

        
        $sizeLimit = $this->sizeLimit;
        if ($size > $this->sizeLimit) {
            $sizeLimit = 0;
        }

        
        $closer = null;

        return new Promise(function ($resolve, $reject) use ($body, &$closer, $sizeLimit, $request, $next) {
            
            $buffer = '';
            $bufferer = null;
            $body->on('data', $bufferer = function ($data) use (&$buffer, $sizeLimit, $body, &$bufferer) {
                $buffer .= $data;

                
                
                
                if (isset($buffer[$sizeLimit])) {
                    assert($bufferer instanceof \Closure);
                    $body->removeListener('data', $bufferer);
                    $bufferer = null;
                    $buffer = '';
                }
            });

            
            $body->on('close', $closer = function () use (&$buffer, $request, $resolve, $reject, $next) {
                try {
                    
                    $resolve($next($request->withBody(new BufferedBody($buffer))));
                } catch (\Exception $e) {
                    $reject($e);
                } catch (\Throwable $e) { 
                    
                    $reject($e); 
                }
            });

            
            $body->on('error', function (\Exception $e) use ($reject, $body, $closer) {
                
                assert($closer instanceof \Closure);
                $body->removeListener('close', $closer);
                $body->close();

                $reject(new \RuntimeException(
                    'Error while buffering request body: ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                ));
            });
        }, function () use ($body, &$closer) {
            
            assert($closer instanceof \Closure);
            $body->removeListener('close', $closer);
            $body->close();

            throw new \RuntimeException('Cancelled buffering request body');
        });
    }
}
