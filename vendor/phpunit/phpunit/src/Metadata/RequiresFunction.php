<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RequiresFunction extends Metadata
{
    
    private readonly string $functionName;

    
    protected function __construct(int $level, string $functionName)
    {
        parent::__construct($level);

        $this->functionName = $functionName;
    }

    
    public function isRequiresFunction(): bool
    {
        return true;
    }

    
    public function functionName(): string
    {
        return $this->functionName;
    }
}
