<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Skipped extends Known
{
    
    public function isSkipped(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 1;
    }

    public function asString(): string
    {
        return 'skipped';
    }
}
