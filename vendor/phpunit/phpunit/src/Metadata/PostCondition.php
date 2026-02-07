<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class PostCondition extends Metadata
{
    
    public function isPostCondition(): bool
    {
        return true;
    }
}
