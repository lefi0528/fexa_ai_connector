<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class Group extends Metadata
{
    
    private readonly string $groupName;

    
    protected function __construct(int $level, string $groupName)
    {
        parent::__construct($level);

        $this->groupName = $groupName;
    }

    
    public function isGroup(): bool
    {
        return true;
    }

    
    public function groupName(): string
    {
        return $this->groupName;
    }
}
