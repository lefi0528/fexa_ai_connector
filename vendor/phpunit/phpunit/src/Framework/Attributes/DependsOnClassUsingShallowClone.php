<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class DependsOnClassUsingShallowClone
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
