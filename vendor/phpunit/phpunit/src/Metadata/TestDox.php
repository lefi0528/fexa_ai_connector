<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class TestDox extends Metadata
{
    
    private readonly string $text;

    
    protected function __construct(int $level, string $text)
    {
        parent::__construct($level);

        $this->text = $text;
    }

    
    public function isTestDox(): bool
    {
        return true;
    }

    
    public function text(): string
    {
        return $this->text;
    }
}
