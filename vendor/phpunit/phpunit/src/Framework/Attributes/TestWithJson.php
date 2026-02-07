<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class TestWithJson
{
    
    private readonly string $json;

    
    public function __construct(string $json)
    {
        $this->json = $json;
    }

    
    public function json(): string
    {
        return $this->json;
    }
}
