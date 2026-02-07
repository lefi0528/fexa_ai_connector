<?php

declare(strict_types=1);

namespace PhpMcp\Client;

use PhpMcp\Client\Exception\TimeoutException;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Utils
{
    
    public static function timeout(
        PromiseInterface $promise,
        float $timeout,
        LoopInterface $loop,
        string $operationName = 'Operation'
    ): PromiseInterface {
        $loop ??= Loop::get();
        $canceller = function () use (&$promise) {
            $promise->cancel();
            $promise = null;
        };

        return new Promise(function ($resolve, $reject) use ($loop, $promise, $timeout, $operationName) {
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

            
            $timer = $loop->addTimer($timeout, function () use ($timeout, &$promise, $reject, $operationName) {
                $reject(new TimeoutException("{$operationName} timed out after {$timeout} seconds", $timeout));

                $promise->cancel();
                $promise = null;
            });
        }, $canceller);
    }
}
