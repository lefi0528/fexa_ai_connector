<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Failure extends Known
{
    
    public function isFailure(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 7;
    }

    public function asString(): string
    {
        return 'failure';
    }
}
