<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class UsesClass
{
    
    private readonly string $className;

    
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    
    public function className(): string
    {
        return $this->className;
    }
}
