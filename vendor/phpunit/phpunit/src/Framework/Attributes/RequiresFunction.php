<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class RequiresFunction
{
    
    private readonly string $functionName;

    
    public function __construct(string $functionName)
    {
        $this->functionName = $functionName;
    }

    
    public function functionName(): string
    {
        return $this->functionName;
    }
}
