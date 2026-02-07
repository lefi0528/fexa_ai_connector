<?php declare(strict_types=1);

namespace PHPUnit\Framework\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class RequiresPhpExtension
{
    
    private readonly string $extension;

    
    private readonly ?string $versionRequirement;

    
    public function __construct(string $extension, ?string $versionRequirement = null)
    {
        $this->extension          = $extension;
        $this->versionRequirement = $versionRequirement;
    }

    
    public function extension(): string
    {
        return $this->extension;
    }

    
    public function versionRequirement(): ?string
    {
        return $this->versionRequirement;
    }
}
