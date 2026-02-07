<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Success extends Known
{
    
    public function isSuccess(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 0;
    }

    public function asString(): string
    {
        return 'success';
    }
}
