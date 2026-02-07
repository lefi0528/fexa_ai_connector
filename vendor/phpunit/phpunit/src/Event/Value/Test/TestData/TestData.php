<?php declare(strict_types=1);

namespace PHPUnit\Event\TestData;


abstract class TestData
{
    private readonly string $data;

    protected function __construct(string $data)
    {
        $this->data = $data;
    }

    public function data(): string
    {
        return $this->data;
    }

    
    public function isFromDataProvider(): bool
    {
        return false;
    }

    
    public function isFromTestDependency(): bool
    {
        return false;
    }
}
