<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class ExcludeGlobalVariableFromBackup extends Metadata
{
    
    private readonly string $globalVariableName;

    
    protected function __construct(int $level, string $globalVariableName)
    {
        parent::__construct($level);

        $this->globalVariableName = $globalVariableName;
    }

    
    public function isExcludeGlobalVariableFromBackup(): bool
    {
        return true;
    }

    
    public function globalVariableName(): string
    {
        return $this->globalVariableName;
    }
}
