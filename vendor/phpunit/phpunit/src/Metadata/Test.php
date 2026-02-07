<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class Test extends Metadata
{
    
    public function isTest(): bool
    {
        return true;
    }
}
