<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestSize;


abstract class Known extends TestSize
{
    
    public function isKnown(): bool
    {
        return true;
    }

    abstract public function isGreaterThan(self $other): bool;
}
