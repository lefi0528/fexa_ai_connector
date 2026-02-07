<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class WithoutErrorHandler extends Metadata
{
    
    public function isWithoutErrorHandler(): bool
    {
        return true;
    }
}
