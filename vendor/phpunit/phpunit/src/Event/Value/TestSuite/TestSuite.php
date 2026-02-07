<?php declare(strict_types=1);

namespace PHPUnit\Event\TestSuite;

use PHPUnit\Event\Code\TestCollection;


abstract class TestSuite
{
    
    private readonly string $name;
    private readonly int $count;
    private readonly TestCollection $tests;

    
    public function __construct(string $name, int $size, TestCollection $tests)
    {
        $this->name  = $name;
        $this->count = $size;
        $this->tests = $tests;
    }

    
    public function name(): string
    {
        return $this->name;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function tests(): TestCollection
    {
        return $this->tests;
    }

    
    public function isWithName(): bool
    {
        return false;
    }

    
    public function isForTestClass(): bool
    {
        return false;
    }

    
    public function isForTestMethodWithDataProvider(): bool
    {
        return false;
    }
}
