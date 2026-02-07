<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Group
{
    
    private readonly string $name;

    
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    
    public function name(): string
    {
        return $this->name;
    }
}
