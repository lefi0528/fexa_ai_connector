<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RunClassInSeparateProcess extends Metadata
{
    
    public function isRunClassInSeparateProcess(): bool
    {
        return true;
    }
}
