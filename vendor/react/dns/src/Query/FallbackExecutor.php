<?php

namespace React\Dns\Query;

use React\Promise\Promise;

final class FallbackExecutor implements ExecutorInterface
{
    private $executor;
    private $fallback;

    public function __construct(ExecutorInterface $executor, ExecutorInterface $fallback)
    {
        $this->executor = $executor;
        $this->fallback = $fallback;
    }

    public function query(Query $query)
    {
        $cancelled = false;
        $fallback = $this->fallback;
        $promise = $this->executor->query($query);

        return new Promise(function ($resolve, $reject) use (&$promise, $fallback, $query, &$cancelled) {
            $promise->then($resolve, function (\Exception $e1) use ($fallback, $query, $resolve, $reject, &$cancelled, &$promise) {
                
                if ($cancelled) {
                    $reject($e1);
                    return;
                }

                
                $promise = $fallback->query($query)->then($resolve, function (\Exception $e2) use ($e1, $reject) {
                    $append = $e2->getMessage();
                    if (($pos = strpos($append, ':')) !== false) {
                        $append = substr($append, $pos + 2);
                    }

                    
                    $reject(new \RuntimeException($e1->getMessage() . '. ' . $append));
                });
            });
        }, function () use (&$promise, &$cancelled) {
            
            $cancelled = true;
            $promise->cancel();
        });
    }
}
