<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject;

use SebastianBergmann\Type\Type;


final class ConfigurableMethod
{
    
    private readonly string $name;

    
    private readonly array $defaultParameterValues;

    
    private readonly int $numberOfParameters;
    private readonly Type $returnType;

    
    public function __construct(string $name, array $defaultParameterValues, int $numberOfParameters, Type $returnType)
    {
        $this->name                   = $name;
        $this->defaultParameterValues = $defaultParameterValues;
        $this->numberOfParameters     = $numberOfParameters;
        $this->returnType             = $returnType;
    }

    
    public function name(): string
    {
        return $this->name;
    }

    
    public function defaultParameterValues(): array
    {
        return $this->defaultParameterValues;
    }

    
    public function numberOfParameters(): int
    {
        return $this->numberOfParameters;
    }

    public function mayReturn(mixed $value): bool
    {
        return $this->returnType->isAssignable(Type::fromValue($value, false));
    }

    public function returnTypeDeclaration(): string
    {
        return $this->returnType->asString();
    }
}
