<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class DoesNotPerformAssertions extends Metadata
{
    
    public function isDoesNotPerformAssertions(): bool
    {
        return true;
    }
}
