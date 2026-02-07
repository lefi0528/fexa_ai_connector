<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use PHPUnit\Util\Exporter;


final class GreaterThan extends Constraint
{
    private readonly mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    
    public function toString(bool $exportObjects = false): string
    {
        return 'is greater than ' . Exporter::export($this->value, $exportObjects);
    }

    
    protected function matches(mixed $other): bool
    {
        return $this->value < $other;
    }
}
