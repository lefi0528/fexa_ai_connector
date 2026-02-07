<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;


abstract class Test
{
    
    private readonly string $file;

    
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    
    public function file(): string
    {
        return $this->file;
    }

    
    public function isTestMethod(): bool
    {
        return false;
    }

    
    public function isPhpt(): bool
    {
        return false;
    }

    
    abstract public function id(): string;

    
    abstract public function name(): string;
}
