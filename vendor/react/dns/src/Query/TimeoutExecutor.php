<?php

namespace React\Dns\Query;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

final class TimeoutExecutor implements ExecutorInterface
{
    private $executor;
    private $loop;
    private $timeout;

    
    public function __construct(ExecutorInterface $executor, $timeout, $loop = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #3 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->executor = $executor;
        $this->loop = $loop ?: Loop::get();
        $this->timeout = $timeout;
    }

    public function query(Query $query)
    {
        $promise = $this->executor->query($query);

        $loop = $this->loop;
        $time = $this->timeout;
        return new Promise(function ($resolve, $reject) use ($loop, $time, $promise, $query) {
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

            
            $timer = $loop->addTimer($time, function () use ($time, &$promise, $reject, $query) {
                $reject(new TimeoutException(
                    'DNS query for ' . $query->describe() . ' timed out'
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
