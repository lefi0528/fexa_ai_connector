<?php declare(strict_types=1);

namespace SebastianBergmann\CodeCoverage\Test\TestStatus;


final class Failure extends Known
{
    
    public function isFailure(): bool
    {
        return true;
    }

    public function asString(): string
    {
        return 'failure';
    }
}
