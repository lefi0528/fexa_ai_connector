<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequiresOperatingSystem
{
    
    private readonly string $regularExpression;

    
    public function __construct(string $regularExpression)
    {
        $this->regularExpression = $regularExpression;
    }

    
    public function regularExpression(): string
    {
        return $this->regularExpression;
    }
}
