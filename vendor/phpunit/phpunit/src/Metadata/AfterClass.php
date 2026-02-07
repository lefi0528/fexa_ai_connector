<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class AfterClass extends Metadata
{
    
    public function isAfterClass(): bool
    {
        return true;
    }
}
