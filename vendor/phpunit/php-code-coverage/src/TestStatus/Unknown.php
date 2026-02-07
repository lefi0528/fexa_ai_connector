<?php declare(strict_types=1);

namespace SebastianBergmann\CodeCoverage\Test\TestStatus;


final class Unknown extends TestStatus
{
    
    public function isUnknown(): bool
    {
        return true;
    }

    public function asString(): string
    {
        return 'unknown';
    }
}
