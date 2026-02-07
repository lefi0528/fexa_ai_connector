<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RequiresOperatingSystem extends Metadata
{
    
    private readonly string $operatingSystem;

    
    public function __construct(int $level, string $operatingSystem)
    {
        parent::__construct($level);

        $this->operatingSystem = $operatingSystem;
    }

    
    public function isRequiresOperatingSystem(): bool
    {
        return true;
    }

    
    public function operatingSystem(): string
    {
        return $this->operatingSystem;
    }
}
