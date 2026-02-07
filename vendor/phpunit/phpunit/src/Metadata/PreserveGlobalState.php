<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class PreserveGlobalState extends Metadata
{
    private readonly bool $enabled;

    
    protected function __construct(int $level, bool $enabled)
    {
        parent::__construct($level);

        $this->enabled = $enabled;
    }

    
    public function isPreserveGlobalState(): bool
    {
        return true;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }
}
