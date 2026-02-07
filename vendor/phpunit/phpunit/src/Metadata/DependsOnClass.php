<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class DependsOnClass extends Metadata
{
    
    private readonly string $className;
    private readonly bool $deepClone;
    private readonly bool $shallowClone;

    
    protected function __construct(int $level, string $className, bool $deepClone, bool $shallowClone)
    {
        parent::__construct($level);

        $this->className    = $className;
        $this->deepClone    = $deepClone;
        $this->shallowClone = $shallowClone;
    }

    
    public function isDependsOnClass(): bool
    {
        return true;
    }

    
    public function className(): string
    {
        return $this->className;
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
