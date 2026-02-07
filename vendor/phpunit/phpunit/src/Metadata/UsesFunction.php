<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class UsesFunction extends Metadata
{
    
    private readonly string $functionName;

    
    public function __construct(int $level, string $functionName)
    {
        parent::__construct($level);

        $this->functionName = $functionName;
    }

    
    public function isUsesFunction(): bool
    {
        return true;
    }

    
    public function functionName(): string
    {
        return $this->functionName;
    }

    
    public function asStringForCodeUnitMapper(): string
    {
        return '::' . $this->functionName;
    }
}
