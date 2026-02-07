<?php

namespace React\Async;

use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;


function async(callable $function): callable
{
    return static function (mixed ...$args) use ($function): PromiseInterface {
        $fiber = null;
        
        $promise = new Promise(function (callable $resolve, callable $reject) use ($function, $args, &$fiber): void {
            $fiber = new \Fiber(function () use ($resolve, $reject, $function, $args, &$fiber): void {
                try {
                    $resolve($function(...$args));
                } catch (\Throwable $exception) {
                    $reject($exception);
                } finally {
                    assert($fiber instanceof \Fiber);
                    FiberMap::unsetPromise($fiber);
                }
            });

            $fiber->start();
        }, function () use (&$fiber): void {
            assert($fiber instanceof \Fiber);
            $promise = FiberMap::getPromise($fiber);
            if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
                $promise->cancel();
            }
        });

        $lowLevelFiber = \Fiber::getCurrent();
        if ($lowLevelFiber !== null) {
            FiberMap::setPromise($lowLevelFiber, $promise);
        }

        return $promise;
    };
}


function await(PromiseInterface $promise): mixed
{
    $fiber = null;
    $resolved = false;
    $rejected = false;

    
    $resolvedValue = null;
    $rejectedThrowable = null;
    $lowLevelFiber = \Fiber::getCurrent();

    $promise->then(
        function (mixed $value) use (&$resolved, &$resolvedValue, &$fiber, $lowLevelFiber): void {
            if ($lowLevelFiber !== null) {
                FiberMap::unsetPromise($lowLevelFiber);
            }

            
            if ($fiber === null) {
                $resolved = true;
                
                $resolvedValue = $value;
                return;
            }

            $fiber->resume($value);
        },
        function (mixed $throwable) use (&$rejected, &$rejectedThrowable, &$fiber, $lowLevelFiber): void {
            if ($lowLevelFiber !== null) {
                FiberMap::unsetPromise($lowLevelFiber);
            }

            if (!$throwable instanceof \Throwable) {
                $throwable = new \UnexpectedValueException(
                    'Promise rejected with unexpected value of type ' . (is_object($throwable) ? get_class($throwable) : gettype($throwable)) 
                );

                
                
                $r = new \ReflectionProperty('Exception', 'trace');
                $trace = $r->getValue($throwable);
                assert(\is_array($trace));

                
                
                foreach ($trace as $ti => $one) {
                    if (isset($one['args'])) {
                        foreach ($one['args'] as $ai => $arg) {
                            if ($arg instanceof \Closure) {
                                $trace[$ti]['args'][$ai] = 'Object(' . \get_class($arg) . ')';
                            }
                        }
                    }
                }
                
                $r->setValue($throwable, $trace);
            }

            if ($fiber === null) {
                $rejected = true;
                $rejectedThrowable = $throwable;
                return;
            }

            $fiber->throw($throwable);
        }
    );

    if ($resolved) {
        return $resolvedValue;
    }

    if ($rejected) {
        assert($rejectedThrowable instanceof \Throwable);
        throw $rejectedThrowable;
    }

    if ($lowLevelFiber !== null) {
        FiberMap::setPromise($lowLevelFiber, $promise);
    }

    $fiber = FiberFactory::create();

    return $fiber->suspend();
}


function delay(float $seconds): void
{
    
    $timer = null;

    await(new Promise(function (callable $resolve) use ($seconds, &$timer): void {
        $timer = Loop::addTimer($seconds, fn() => $resolve(null));
    }, function () use (&$timer): void {
        assert($timer instanceof TimerInterface);
        Loop::cancelTimer($timer);
        throw new \RuntimeException('Delay cancelled');
    }));
}


function coroutine(callable $function, mixed ...$args): PromiseInterface
{
    try {
        $generator = $function(...$args);
    } catch (\Throwable $e) {
        return reject($e);
    }

    if (!$generator instanceof \Generator) {
        return resolve($generator);
    }

    $promise = null;
    
    $deferred = new Deferred(function () use (&$promise) {
        
        if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
            $promise->cancel();
        }
        $promise = null;
    });

    
    $next = function () use ($deferred, $generator, &$next, &$promise) {
        try {
            if (!$generator->valid()) {
                $next = null;
                $deferred->resolve($generator->getReturn());
                return;
            }
        } catch (\Throwable $e) {
            $next = null;
            $deferred->reject($e);
            return;
        }

        $promise = $generator->current();
        if (!$promise instanceof PromiseInterface) {
            $next = null;
            $deferred->reject(new \UnexpectedValueException(
                'Expected coroutine to yield ' . PromiseInterface::class . ', but got ' . (is_object($promise) ? get_class($promise) : gettype($promise))
            ));
            return;
        }

        
        assert($next instanceof \Closure);
        $promise->then(function ($value) use ($generator, $next) {
            $generator->send($value);
            $next();
        }, function (\Throwable $reason) use ($generator, $next) {
            $generator->throw($reason);
            $next();
        })->then(null, function (\Throwable $reason) use ($deferred, &$next) {
            $next = null;
            $deferred->reject($reason);
        });
    };
    $next();

    return $deferred->promise();
}


function parallel(iterable $tasks): PromiseInterface
{
    
    $pending = [];
    
    $deferred = new Deferred(function () use (&$pending) {
        foreach ($pending as $promise) {
            if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
                $promise->cancel();
            }
        }
        $pending = [];
    });
    $results = [];
    $continue = true;

    $taskErrback = function ($error) use (&$pending, $deferred, &$continue) {
        $continue = false;
        $deferred->reject($error);

        foreach ($pending as $promise) {
            if ($promise instanceof PromiseInterface && \method_exists($promise, 'cancel')) {
                $promise->cancel();
            }
        }
        $pending = [];
    };

    foreach ($tasks as $i => $task) {
        $taskCallback = function ($result) use (&$results, &$pending, &$continue, $i, $deferred) {
            $results[$i] = $result;
            unset($pending[$i]);

            if (!$pending && !$continue) {
                $deferred->resolve($results);
            }
        };

        $promise = \call_user_func($task);
        assert($promise instanceof PromiseInterface);
        $pending[$i] = $promise;

        $promise->then($taskCallback, $taskErrback);

        if (!$continue) {
            break;
        }
    }

    $continue = false;
    if (!$pending) {
        $deferred->resolve($results);
    }

    
    return $deferred->promise();
}


function series(iterable $tasks): PromiseInterface
{
    $pending = null;
    
    $deferred = new Deferred(function () use (&$pending) {
        
        if ($pending instanceof PromiseInterface && \method_exists($pending, 'cancel')) {
            $pending->cancel();
        }
        $pending = null;
    });
    $results = [];

    if ($tasks instanceof \IteratorAggregate) {
        $tasks = $tasks->getIterator();
        assert($tasks instanceof \Iterator);
    }

    $taskCallback = function ($result) use (&$results, &$next) {
        $results[] = $result;
        
        $next();
    };

    $next = function () use (&$tasks, $taskCallback, $deferred, &$results, &$pending) {
        if ($tasks instanceof \Iterator ? !$tasks->valid() : !$tasks) {
            $deferred->resolve($results);
            return;
        }

        if ($tasks instanceof \Iterator) {
            $task = $tasks->current();
            $tasks->next();
        } else {
            assert(\is_array($tasks));
            $task = \array_shift($tasks);
        }

        assert(\is_callable($task));
        $promise = \call_user_func($task);
        assert($promise instanceof PromiseInterface);
        $pending = $promise;

        $promise->then($taskCallback, array($deferred, 'reject'));
    };

    $next();

    
    return $deferred->promise();
}


function waterfall(iterable $tasks): PromiseInterface
{
    $pending = null;
    
    $deferred = new Deferred(function () use (&$pending) {
        
        if ($pending instanceof PromiseInterface && \method_exists($pending, 'cancel')) {
            $pending->cancel();
        }
        $pending = null;
    });

    if ($tasks instanceof \IteratorAggregate) {
        $tasks = $tasks->getIterator();
        assert($tasks instanceof \Iterator);
    }

    
    $next = function ($value = null) use (&$tasks, &$next, $deferred, &$pending) {
        if ($tasks instanceof \Iterator ? !$tasks->valid() : !$tasks) {
            $deferred->resolve($value);
            return;
        }

        if ($tasks instanceof \Iterator) {
            $task = $tasks->current();
            $tasks->next();
        } else {
            assert(\is_array($tasks));
            $task = \array_shift($tasks);
        }

        assert(\is_callable($task));
        $promise = \call_user_func_array($task, func_get_args());
        assert($promise instanceof PromiseInterface);
        $pending = $promise;

        $promise->then($next, array($deferred, 'reject'));
    };

    $next();

    return $deferred->promise();
}
