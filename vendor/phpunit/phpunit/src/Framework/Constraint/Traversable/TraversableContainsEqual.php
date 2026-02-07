<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use SplObjectStorage;


final class TraversableContainsEqual extends TraversableContains
{
    
    protected function matches(mixed $other): bool
    {
        if ($other instanceof SplObjectStorage) {
            return $other->offsetExists($this->value());
        }

        foreach ($other as $element) {
            
            if ($this->value() == $element) {
                return true;
            }
        }

        return false;
    }
}
