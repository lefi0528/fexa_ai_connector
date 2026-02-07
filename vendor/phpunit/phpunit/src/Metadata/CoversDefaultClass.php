<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class CoversDefaultClass extends Metadata
{
    
    private readonly string $className;

    
    protected function __construct(int $level, string $className)
    {
        parent::__construct($level);

        $this->className = $className;
    }

    
    public function isCoversDefaultClass(): bool
    {
        return true;
    }

    
    public function className(): string
    {
        return $this->className;
    }
}
