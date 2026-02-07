<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequiresOperatingSystemFamily
{
    
    private readonly string $operatingSystemFamily;

    
    public function __construct(string $operatingSystemFamily)
    {
        $this->operatingSystemFamily = $operatingSystemFamily;
    }

    
    public function operatingSystemFamily(): string
    {
        return $this->operatingSystemFamily;
    }
}
