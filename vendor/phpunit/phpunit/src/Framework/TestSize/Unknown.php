<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestSize;


final class Unknown extends TestSize
{
    
    public function isUnknown(): bool
    {
        return true;
    }

    public function asString(): string
    {
        return 'unknown';
    }
}
