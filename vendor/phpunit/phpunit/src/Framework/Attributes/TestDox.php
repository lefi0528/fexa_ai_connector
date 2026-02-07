<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class TestDox
{
    
    private readonly string $text;

    
    public function __construct(string $text)
    {
        $this->text = $text;
    }

    
    public function text(): string
    {
        return $this->text;
    }
}
