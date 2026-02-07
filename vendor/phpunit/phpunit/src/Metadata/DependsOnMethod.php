<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class DependsOnMethod extends Metadata
{
    
    private readonly string $className;

    
    private readonly string $methodName;
    private readonly bool $deepClone;
    private readonly bool $shallowClone;

    
    protected function __construct(int $level, string $className, string $methodName, bool $deepClone, bool $shallowClone)
    {
        parent::__construct($level);

        $this->className    = $className;
        $this->methodName   = $methodName;
        $this->deepClone    = $deepClone;
        $this->shallowClone = $shallowClone;
    }

    
    public function isDependsOnMethod(): bool
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

    public function deepClone(): bool
    {
        return $this->deepClone;
    }

    public function shallowClone(): bool
    {
        return $this->shallowClone;
    }
}
