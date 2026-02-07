<?php declare(strict_types=1);

namespace PHPUnit\Runner\GarbageCollection;


abstract class Subscriber
{
    private readonly GarbageCollectionHandler $handler;

    public function __construct(GarbageCollectionHandler $handler)
    {
        $this->handler = $handler;
    }

    protected function handler(): GarbageCollectionHandler
    {
        return $this->handler;
    }
}
