<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestSize;


final class Small extends Known
{
    
    public function isSmall(): bool
    {
        return true;
    }

    public function isGreaterThan(TestSize $other): bool
    {
        return false;
    }

    public function asString(): string
    {
        return 'small';
    }
}
