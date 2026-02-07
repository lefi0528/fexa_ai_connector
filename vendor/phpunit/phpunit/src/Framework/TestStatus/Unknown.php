<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Unknown extends TestStatus
{
    
    public function isUnknown(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return -1;
    }

    public function asString(): string
    {
        return 'unknown';
    }
}
