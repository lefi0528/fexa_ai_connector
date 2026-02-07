<?php declare(strict_types=1);

namespace SebastianBergmann\CodeCoverage\Test\TestSize;


final class Medium extends Known
{
    
    public function isMedium(): bool
    {
        return true;
    }

    public function isGreaterThan(TestSize $other): bool
    {
        return $other->isSmall();
    }

    public function asString(): string
    {
        return 'medium';
    }
}
