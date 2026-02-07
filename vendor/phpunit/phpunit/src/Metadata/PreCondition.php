<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class PreCondition extends Metadata
{
    
    public function isPreCondition(): bool
    {
        return true;
    }
}
