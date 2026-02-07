<?php declare(strict_types=1);

namespace PHPUnit\Event\TestSuite;


final class TestSuiteWithName extends TestSuite
{
    
    public function isWithName(): bool
    {
        return true;
    }
}
