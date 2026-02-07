<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


abstract class Known extends TestStatus
{
    
    public function isKnown(): bool
    {
        return true;
    }
}
