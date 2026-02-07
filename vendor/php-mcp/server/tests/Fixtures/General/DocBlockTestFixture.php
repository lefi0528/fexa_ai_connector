<?php

namespace PhpMcp\Server\Tests\Fixtures\General;


class DocBlockTestFixture
{
    
    public function methodWithSummaryOnly(): void
    {
    }

    
    public function methodWithSummaryAndDescription(): void
    {
    }

    
    public function methodWithParams(string $param1, ?int $param2, bool $param3, $param4, array $param5, \stdClass $param6): void
    {
    }

    
    public function methodWithReturn(): string
    {
        return '';
    }

    
    public function methodWithMultipleTags(float $value): bool
    {
        return true;
    }

    
    public function methodWithMalformedDocBlock(): void
    {
    }

    public function methodWithNoDocBlock(): void
    {
    }

    
    public function newMethod(): void
    {
    }
}
