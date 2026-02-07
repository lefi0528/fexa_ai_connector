<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class Covers extends Metadata
{
    
    private readonly string $target;

    
    protected function __construct(int $level, string $target)
    {
        parent::__construct($level);

        $this->target = $target;
    }

    
    public function isCovers(): bool
    {
        return true;
    }

    
    public function target(): string
    {
        return $this->target;
    }
}
