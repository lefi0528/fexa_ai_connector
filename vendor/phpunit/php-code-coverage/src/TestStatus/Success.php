<?php declare(strict_types=1);

namespace SebastianBergmann\CodeCoverage\Test\TestStatus;


final class Success extends Known
{
    
    public function isSuccess(): bool
    {
        return true;
    }

    public function asString(): string
    {
        return 'success';
    }
}
