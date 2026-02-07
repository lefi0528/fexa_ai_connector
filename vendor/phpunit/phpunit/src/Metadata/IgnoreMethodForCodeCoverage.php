<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class IgnoreMethodForCodeCoverage extends Metadata
{
    
    private readonly string $className;

    
    private readonly string $methodName;

    
    protected function __construct(int $level, string $className, string $methodName)
    {
        parent::__construct($level);

        $this->className  = $className;
        $this->methodName = $methodName;
    }

    
    public function isIgnoreMethodForCodeCoverage(): bool
    {
        return true;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function methodName(): string
    {
        return $this->methodName;
    }
}
