<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class BeforeClass extends Metadata
{
    
    public function isBeforeClass(): bool
    {
        return true;
    }
}
