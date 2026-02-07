<?php declare(strict_types=1);

namespace PHPUnit\Metadata;

use PHPUnit\Metadata\Version\Requirement;


final class RequiresPhp extends Metadata
{
    private readonly Requirement $versionRequirement;

    
    protected function __construct(int $level, Requirement $versionRequirement)
    {
        parent::__construct($level);

        $this->versionRequirement = $versionRequirement;
    }

    
    public function isRequiresPhp(): bool
    {
        return true;
    }

    public function versionRequirement(): Requirement
    {
        return $this->versionRequirement;
    }
}
