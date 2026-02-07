<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Warning extends Known
{
    
    public function isWarning(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 6;
    }

    public function asString(): string
    {
        return 'warning';
    }
}
