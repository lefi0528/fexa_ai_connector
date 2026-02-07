<?php

namespace React\Async;

use React\Promise\PromiseInterface;


final class FiberMap
{
    
    private static array $map = [];

    
    public static function setPromise(\Fiber $fiber, PromiseInterface $promise): void
    {
        self::$map[\spl_object_id($fiber)] = $promise;
    }

    
    public static function unsetPromise(\Fiber $fiber): void
    {
        unset(self::$map[\spl_object_id($fiber)]);
    }

    
    public static function getPromise(\Fiber $fiber): ?PromiseInterface
    {
        return self::$map[\spl_object_id($fiber)] ?? null;
    }
}
