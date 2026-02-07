<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject\Generator;

use function class_exists;


final class MockTrait implements MockType
{
    private readonly string $classCode;

    
    private readonly string $mockName;

    
    public function __construct(string $classCode, string $mockName)
    {
        $this->classCode = $classCode;
        $this->mockName  = $mockName;
    }

    
    public function generate(): string
    {
        if (!class_exists($this->mockName, false)) {
            eval($this->classCode);
        }

        return $this->mockName;
    }
}
