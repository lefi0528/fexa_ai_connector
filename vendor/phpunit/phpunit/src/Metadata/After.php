<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class After extends Metadata
{
    
    public function isAfter(): bool
    {
        return true;
    }
}
