<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ExcludeGlobalVariableFromBackup
{
    
    private readonly string $globalVariableName;

    
    public function __construct(string $globalVariableName)
    {
        $this->globalVariableName = $globalVariableName;
    }

    
    public function globalVariableName(): string
    {
        return $this->globalVariableName;
    }
}
