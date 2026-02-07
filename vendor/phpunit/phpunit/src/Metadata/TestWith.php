<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class TestWith extends Metadata
{
    private readonly mixed $data;

    
    protected function __construct(int $level, mixed $data)
    {
        parent::__construct($level);

        $this->data = $data;
    }

    
    public function isTestWith(): bool
    {
        return true;
    }

    public function data(): mixed
    {
        return $this->data;
    }
}
