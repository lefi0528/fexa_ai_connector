<?php declare(strict_types=1);

namespace PHPUnit\Event\TestSuite;

use PHPUnit\Event\Code\TestCollection;


final class TestSuiteForTestMethodWithDataProvider extends TestSuite
{
    
    private readonly string $className;

    
    private readonly string $methodName;
    private readonly string $file;
    private readonly int $line;

    
    public function __construct(string $name, int $size, TestCollection $tests, string $className, string $methodName, string $file, int $line)
    {
        parent::__construct($name, $size, $tests);

        $this->className  = $className;
        $this->methodName = $methodName;
        $this->file       = $file;
        $this->line       = $line;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function methodName(): string
    {
        return $this->methodName;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function line(): int
    {
        return $this->line;
    }

    
    public function isForTestMethodWithDataProvider(): bool
    {
        return true;
    }
}
