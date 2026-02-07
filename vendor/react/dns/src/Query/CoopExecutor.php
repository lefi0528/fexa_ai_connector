<?php

namespace React\Dns\Query;

use React\Promise\Promise;


final class CoopExecutor implements ExecutorInterface
{
    private $executor;
    private $pending = array();
    private $counts = array();

    public function __construct(ExecutorInterface $base)
    {
        $this->executor = $base;
    }

    public function query(Query $query)
    {
        $key = $this->serializeQueryToIdentity($query);
        if (isset($this->pending[$key])) {
            
            $promise = $this->pending[$key];
            ++$this->counts[$key];
        } else {
            
            $promise = $this->executor->query($query);
            $this->pending[$key] = $promise;
            $this->counts[$key] = 1;

            $pending =& $this->pending;
            $counts =& $this->counts;
            $promise->then(function () use ($key, &$pending, &$counts) {
                unset($pending[$key], $counts[$key]);
            }, function () use ($key, &$pending, &$counts) {
                unset($pending[$key], $counts[$key]);
            });
        }

        
        
        
        $pending =& $this->pending;
        $counts =& $this->counts;
        return new Promise(function ($resolve, $reject) use ($promise) {
            $promise->then($resolve, $reject);
        }, function () use (&$promise, $key, $query, &$pending, &$counts) {
            if (--$counts[$key] < 1) {
                unset($pending[$key], $counts[$key]);
                $promise->cancel();
                $promise = null;
            }
            throw new \RuntimeException('DNS query for ' . $query->describe() . ' has been cancelled');
        });
    }

    private function serializeQueryToIdentity(Query $query)
    {
        return sprintf('%s:%s:%s', $query->name, $query->type, $query->class);
    }
}
