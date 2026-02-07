<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;


final class ComparisonFailure
{
    private readonly string $expected;
    private readonly string $actual;
    private readonly string $diff;

    public function __construct(string $expected, string $actual, string $diff)
    {
        $this->expected = $expected;
        $this->actual   = $actual;
        $this->diff     = $diff;
    }

    public function expected(): string
    {
        return $this->expected;
    }

    public function actual(): string
    {
        return $this->actual;
    }

    public function diff(): string
    {
        return $this->diff;
    }
}
