<?php declare(strict_types=1);

namespace PHPUnit\Event\TestSuite;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class Skipped implements Event
{
    private readonly Telemetry\Info $telemetryInfo;
    private readonly TestSuite $testSuite;
    private readonly string $message;

    public function __construct(Telemetry\Info $telemetryInfo, TestSuite $testSuite, string $message)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->testSuite     = $testSuite;
        $this->message       = $message;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function testSuite(): TestSuite
    {
        return $this->testSuite;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function asString(): string
    {
        return sprintf(
            'Test Suite Skipped (%s, %s)',
            $this->testSuite->name(),
            $this->message,
        );
    }
}
