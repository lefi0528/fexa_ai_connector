<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RequiresOperatingSystemFamily extends Metadata
{
    
    private readonly string $operatingSystemFamily;

    
    protected function __construct(int $level, string $operatingSystemFamily)
    {
        parent::__construct($level);

        $this->operatingSystemFamily = $operatingSystemFamily;
    }

    
    public function isRequiresOperatingSystemFamily(): bool
    {
        return true;
    }

    
    public function operatingSystemFamily(): string
    {
        return $this->operatingSystemFamily;
    }
}
