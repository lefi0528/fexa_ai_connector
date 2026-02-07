<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use PHPUnit\Util\Exporter;


final class LessThan extends Constraint
{
    private readonly mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    
    public function toString(bool $exportObjects = false): string
    {
        return 'is less than ' . Exporter::export($this->value, $exportObjects);
    }

    
    protected function matches(mixed $other): bool
    {
        return $this->value > $other;
    }
}
