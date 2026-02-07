<?php declare(strict_types=1);

namespace PHPUnit\Framework\Constraint;

use function sprintf;
use PHPUnit\Util\Exporter;


final class ExceptionCode extends Constraint
{
    private readonly int|string $expectedCode;

    public function __construct(int|string $expected)
    {
        $this->expectedCode = $expected;
    }

    public function toString(): string
    {
        return 'exception code is ' . $this->expectedCode;
    }

    
    protected function matches(mixed $other): bool
    {
        return (string) $other === (string) $this->expectedCode;
    }

    
    protected function failureDescription(mixed $other): string
    {
        return sprintf(
            '%s is equal to expected exception code %s',
            Exporter::export($other, true),
            Exporter::export($this->expectedCode, true),
        );
    }
}
