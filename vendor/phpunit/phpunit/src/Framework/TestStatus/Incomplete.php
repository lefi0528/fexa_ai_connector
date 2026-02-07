<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Incomplete extends Known
{
    
    public function isIncomplete(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 2;
    }

    public function asString(): string
    {
        return 'incomplete';
    }
}
