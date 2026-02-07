<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


final class Notice extends Known
{
    
    public function isNotice(): bool
    {
        return true;
    }

    public function asInt(): int
    {
        return 3;
    }

    public function asString(): string
    {
        return 'notice';
    }
}
