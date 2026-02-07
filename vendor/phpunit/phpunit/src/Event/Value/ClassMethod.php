<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;


final class ClassMethod
{
    
    private readonly string $className;

    
    private readonly string $methodName;

    
    public function __construct(string $className, string $methodName)
    {
        $this->className  = $className;
        $this->methodName = $methodName;
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
