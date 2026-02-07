<?php

namespace React\Async;

use React\EventLoop\Loop;


final class SimpleFiber implements FiberInterface
{
    
    private static ?\Fiber $scheduler = null;

    private static ?\Closure $suspend = null;

    
    private ?\Fiber $fiber = null;

    public function __construct()
    {
        $this->fiber = \Fiber::getCurrent();
    }

    public function resume(mixed $value): void
    {
        if ($this->fiber !== null) {
            $this->fiber->resume($value);
        } else {
            self::$suspend = static fn() => $value;
        }

        if (self::$suspend !== null && \Fiber::getCurrent() === self::$scheduler) {
            $suspend = self::$suspend;
            self::$suspend = null;

            \Fiber::suspend($suspend);
        }
    }

    public function throw(\Throwable $throwable): void
    {
        if ($this->fiber !== null) {
            $this->fiber->throw($throwable);
        } else {
            self::$suspend = static fn() => throw $throwable;
        }

        if (self::$suspend !== null && \Fiber::getCurrent() === self::$scheduler) {
            $suspend = self::$suspend;
            self::$suspend = null;

            \Fiber::suspend($suspend);
        }
    }

    public function suspend(): mixed
    {
        if ($this->fiber === null) {
            if (self::$scheduler === null || self::$scheduler->isTerminated()) {
                self::$scheduler = new \Fiber(static fn() => Loop::run());
                
                \register_shutdown_function(static function (): void {
                    assert(self::$scheduler instanceof \Fiber);
                    if (self::$scheduler->isSuspended()) {
                        self::$scheduler->resume();
                    }
                });
            }

            $ret = (self::$scheduler->isStarted() ? self::$scheduler->resume() : self::$scheduler->start());
            assert(\is_callable($ret));

            return $ret();
        }

        return \Fiber::suspend();
    }
}
