<?php declare(strict_types=1);

namespace PHPUnit\Logging\TestDox;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Framework\TestStatus\TestStatus;


final class TestResult
{
    private readonly TestMethod $test;
    private readonly TestStatus $status;
    private readonly ?Throwable $throwable;

    public function __construct(TestMethod $test, TestStatus $status, ?Throwable $throwable)
    {
        $this->test      = $test;
        $this->status    = $status;
        $this->throwable = $throwable;
    }

    public function test(): TestMethod
    {
        return $this->test;
    }

    public function status(): TestStatus
    {
        return $this->status;
    }

    
    public function hasThrowable(): bool
    {
        return $this->throwable !== null;
    }

    public function throwable(): ?Throwable
    {
        return $this->throwable;
    }
}
