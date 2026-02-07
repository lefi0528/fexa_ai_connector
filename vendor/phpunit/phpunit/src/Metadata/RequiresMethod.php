<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RequiresMethod extends Metadata
{
    
    private readonly string $className;

    
    private readonly string $methodName;

    
    protected function __construct(int $level, string $className, string $methodName)
    {
        parent::__construct($level);

        $this->className  = $className;
        $this->methodName = $methodName;
    }

    
    public function isRequiresMethod(): bool
    {
        return true;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function methodName(): string
    {
        return $this->methodName;
    }
}
