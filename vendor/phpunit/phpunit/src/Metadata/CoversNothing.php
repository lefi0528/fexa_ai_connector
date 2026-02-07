<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class CoversNothing extends Metadata
{
    
    public function isCoversNothing(): bool
    {
        return true;
    }
}
