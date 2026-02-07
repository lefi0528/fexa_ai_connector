<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use function is_nan;


final class IsNan extends Constraint
{
    
    public function toString(): string
    {
        return 'is nan';
    }

    
    protected function matches(mixed $other): bool
    {
        return is_nan($other);
    }
}
