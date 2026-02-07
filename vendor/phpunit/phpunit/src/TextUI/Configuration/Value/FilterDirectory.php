<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;


final class FilterDirectory
{
    
    private readonly string $path;
    private readonly string $prefix;
    private readonly string $suffix;

    
    public function __construct(string $path, string $prefix, string $suffix)
    {
        $this->path   = $path;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    
    public function path(): string
    {
        return $this->path;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function suffix(): string
    {
        return $this->suffix;
    }
}
