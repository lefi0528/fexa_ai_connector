<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RunInSeparateProcess extends Metadata
{
    
    public function isRunInSeparateProcess(): bool
    {
        return true;
    }
}
