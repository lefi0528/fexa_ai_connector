<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;


final class SuccessfulSchemaDetectionResult extends SchemaDetectionResult
{
    
    private readonly string $version;

    
    public function __construct(string $version)
    {
        $this->version = $version;
    }

    
    public function detected(): bool
    {
        return true;
    }

    
    public function version(): string
    {
        return $this->version;
    }
}
