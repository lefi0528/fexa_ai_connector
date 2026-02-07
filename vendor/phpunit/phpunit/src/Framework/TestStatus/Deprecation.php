<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Deprecation extends Known
{
    
    public function isDeprecation(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 4;
    }

    public function asString(): string
    {
        return 'deprecation';
    }
}
