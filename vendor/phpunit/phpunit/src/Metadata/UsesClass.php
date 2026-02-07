<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class UsesClass extends Metadata
{
    
    private readonly string $className;

    
    protected function __construct(int $level, string $className)
    {
        parent::__construct($level);

        $this->className = $className;
    }

    
    public function isUsesClass(): bool
    {
        return true;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function asStringForCodeUnitMapper(): string
    {
        return $this->className;
    }
}
