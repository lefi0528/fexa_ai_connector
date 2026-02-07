<?php declare(strict_types=1);

namespace SebastianBergmann\CodeCoverage\Test\TestStatus;


abstract class Known extends TestStatus
{
    
    public function isKnown(): bool
    {
        return true;
    }
}
