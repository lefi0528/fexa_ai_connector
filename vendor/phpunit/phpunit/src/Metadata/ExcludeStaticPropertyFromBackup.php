<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class ExcludeStaticPropertyFromBackup extends Metadata
{
    
    private readonly string $className;

    
    private readonly string $propertyName;

    
    protected function __construct(int $level, string $className, string $propertyName)
    {
        parent::__construct($level);

        $this->className    = $className;
        $this->propertyName = $propertyName;
    }

    
    public function isExcludeStaticPropertyFromBackup(): bool
    {
        return true;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function propertyName(): string
    {
        return $this->propertyName;
    }
}
