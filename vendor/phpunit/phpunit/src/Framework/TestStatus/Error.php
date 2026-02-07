<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Error extends Known
{
    
    public function isError(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 8;
    }

    public function asString(): string
    {
        return 'error';
    }
}
