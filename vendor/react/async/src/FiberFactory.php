<?php

namespace React\Async;


final class FiberFactory
{
    private static ?\Closure $factory = null;

    public static function create(): FiberInterface
    {
        return (self::factory())();
    }

    public static function factory(?\Closure $factory = null): \Closure
    {
        if ($factory !== null) {
            self::$factory = $factory;
        }

        return self::$factory ?? static fn (): FiberInterface => new SimpleFiber();
    }
}
