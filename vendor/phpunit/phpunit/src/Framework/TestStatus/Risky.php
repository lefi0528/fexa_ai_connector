<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Risky extends Known
{
    
    public function isRisky(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 5;
    }

    public function asString(): string
    {
        return 'risky';
    }
}
