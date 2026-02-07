<?php

namespace React\Socket;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

final class TimeoutConnector implements ConnectorInterface
{
    private $connector;
    private $timeout;
    private $loop;

    
    public function __construct(ConnectorInterface $connector, $timeout, $loop = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #3 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->connector = $connector;
        $this->timeout = $timeout;
        $this->loop = $loop ?: Loop::get();
    }

    public function connect($uri)
    {
        $promise = $this->connector->connect($uri);

        $loop = $this->loop;
        $time = $this->timeout;
        return new Promise(function ($resolve, $reject) use ($loop, $time, $promise, $uri) {
            $timer = null;
            $promise = $promise->then(function ($v) use (&$timer, $loop, $resolve) {
                if ($timer) {
                    $loop->cancelTimer($timer);
                }
                $timer = false;
                $resolve($v);
            }, function ($v) use (&$timer, $loop, $reject) {
                if ($timer) {
                    $loop->cancelTimer($timer);
                }
                $timer = false;
                $reject($v);
            });

            
            if ($timer === false) {
                return;
            }

            
            $timer = $loop->addTimer($time, function () use ($time, &$promise, $reject, $uri) {
                $reject(new \RuntimeException(
                    'Connection to ' . $uri . ' timed out after ' . $time . ' seconds (ETIMEDOUT)',
                    \defined('SOCKET_ETIMEDOUT') ? \SOCKET_ETIMEDOUT : 110
                ));

                
                
                assert(\method_exists($promise, 'cancel'));
                $promise->cancel();
                $promise = null;
            });
        }, function () use (&$promise) {
            
            
            assert(\method_exists($promise, 'cancel'));
            $promise->cancel();
            $promise = null;
        });
    }
}
