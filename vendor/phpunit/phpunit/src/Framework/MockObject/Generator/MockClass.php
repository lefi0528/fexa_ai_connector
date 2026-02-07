<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject\Generator;

use function call_user_func;
use function class_exists;
use PHPUnit\Framework\MockObject\ConfigurableMethod;


final class MockClass implements MockType
{
    private readonly string $classCode;

    
    private readonly string $mockName;

    
    private readonly array $configurableMethods;

    
    public function __construct(string $classCode, string $mockName, array $configurableMethods)
    {
        $this->classCode           = $classCode;
        $this->mockName            = $mockName;
        $this->configurableMethods = $configurableMethods;
    }

    
    public function generate(): string
    {
        if (!class_exists($this->mockName, false)) {
            eval($this->classCode);

            call_user_func(
                [
                    $this->mockName,
                    '__phpunit_initConfigurableMethods',
                ],
                ...$this->configurableMethods,
            );
        }

        return $this->mockName;
    }

    public function classCode(): string
    {
        return $this->classCode;
    }
}
