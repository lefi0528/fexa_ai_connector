<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use function gettype;
use function sprintf;
use function strtolower;
use Countable;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\SelfDescribing;
use PHPUnit\Util\Exporter;
use SebastianBergmann\Comparator\ComparisonFailure;


abstract class Constraint implements Countable, SelfDescribing
{
    
    public function evaluate(mixed $other, string $description = '', bool $returnResult = false): ?bool
    {
        $success = false;

        if ($this->matches($other)) {
            $success = true;
        }

        if ($returnResult) {
            return $success;
        }

        if (!$success) {
            $this->fail($other, $description);
        }

        return null;
    }

    
    public function count(): int
    {
        return 1;
    }

    
    protected function exporter(): \SebastianBergmann\Exporter\Exporter
    {
        return new \SebastianBergmann\Exporter\Exporter;
    }

    
    protected function matches(mixed $other): bool
    {
        return false;
    }

    
    protected function fail(mixed $other, string $description, ?ComparisonFailure $comparisonFailure = null): never
    {
        $failureDescription = sprintf(
            'Failed asserting that %s.',
            $this->failureDescription($other),
        );

        $additionalFailureDescription = $this->additionalFailureDescription($other);

        if ($additionalFailureDescription) {
            $failureDescription .= "\n" . $additionalFailureDescription;
        }

        if (!empty($description)) {
            $failureDescription = $description . "\n" . $failureDescription;
        }

        throw new ExpectationFailedException(
            $failureDescription,
            $comparisonFailure,
        );
    }

    
    protected function additionalFailureDescription(mixed $other): string
    {
        return '';
    }

    
    protected function failureDescription(mixed $other): string
    {
        return Exporter::export($other, true) . ' ' . $this->toString(true);
    }

    
    protected function toStringInContext(Operator $operator, mixed $role): string
    {
        return '';
    }

    
    protected function failureDescriptionInContext(Operator $operator, mixed $role, mixed $other): string
    {
        $string = $this->toStringInContext($operator, $role);

        if ($string === '') {
            return '';
        }

        return Exporter::export($other, true) . ' ' . $string;
    }

    
    protected function reduce(): self
    {
        return $this;
    }

    
    protected function valueToTypeStringFragment(mixed $value): string
    {
        $type = strtolower(gettype($value));

        if ($type === 'double') {
            $type = 'float';
        }

        if ($type === 'resource (closed)') {
            $type = 'closed resource';
        }

        return match ($type) {
            'array', 'integer', 'object' => 'an ' . $type . ' ',
            'boolean', 'closed resource', 'float', 'resource', 'string' => 'a ' . $type . ' ',
            'null'  => 'null ',
            default => 'a value of ' . $type . ' ',
        };
    }
}
