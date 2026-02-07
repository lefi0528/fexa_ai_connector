<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RunTestsInSeparateProcesses extends Metadata
{
    
    public function isRunTestsInSeparateProcesses(): bool
    {
        return true;
    }
}
