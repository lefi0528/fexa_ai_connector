<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use function is_string;
use function sprintf;
use function str_contains;
use function trim;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Util\Exporter;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;


final class IsEqual extends Constraint
{
    private readonly mixed $value;
    private readonly float $delta;
    private readonly bool $canonicalize;
    private readonly bool $ignoreCase;

    public function __construct(mixed $value, float $delta = 0.0, bool $canonicalize = false, bool $ignoreCase = false)
    {
        $this->value        = $value;
        $this->delta        = $delta;
        $this->canonicalize = $canonicalize;
        $this->ignoreCase   = $ignoreCase;
    }

    
    public function evaluate(mixed $other, string $description = '', bool $returnResult = false): ?bool
    {
        
        
        
        if ($this->value === $other) {
            return true;
        }

        $comparatorFactory = ComparatorFactory::getInstance();

        try {
            $comparator = $comparatorFactory->getComparatorFor(
                $this->value,
                $other,
            );

            $comparator->assertEquals(
                $this->value,
                $other,
                $this->delta,
                $this->canonicalize,
                $this->ignoreCase,
            );
        } catch (ComparisonFailure $f) {
            if ($returnResult) {
                return false;
            }

            throw new ExpectationFailedException(
                trim($description . "\n" . $f->getMessage()),
                $f,
            );
        }

        return true;
    }

    
    public function toString(bool $exportObjects = false): string
    {
        $delta = '';

        if (is_string($this->value)) {
            if (str_contains($this->value, "\n")) {
                return 'is equal to <text>';
            }

            return sprintf(
                "is equal to '%s'",
                $this->value,
            );
        }

        if ($this->delta != 0) {
            $delta = sprintf(
                ' with delta <%F>',
                $this->delta,
            );
        }

        return sprintf(
            'is equal to %s%s',
            Exporter::export($this->value, $exportObjects),
            $delta,
        );
    }
}
