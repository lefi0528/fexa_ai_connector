<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class Before extends Metadata
{
    
    public function isBefore(): bool
    {
        return true;
    }
}
