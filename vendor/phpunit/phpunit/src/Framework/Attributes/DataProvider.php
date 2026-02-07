<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class DataProvider
{
    
    private readonly string $methodName;

    
    public function __construct(string $methodName)
    {
        $this->methodName = $methodName;
    }

    
    public function methodName(): string
    {
        return $this->methodName;
    }
}
