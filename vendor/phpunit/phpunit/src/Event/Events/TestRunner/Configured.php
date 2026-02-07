<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;
use PHPUnit\TextUI\Configuration\Configuration;


final class Configured implements Event
{
    private readonly Telemetry\Info $telemetryInfo;
    private readonly Configuration $configuration;

    public function __construct(Telemetry\Info $telemetryInfo, Configuration $configuration)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->configuration = $configuration;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function configuration(): Configuration
    {
        return $this->configuration;
    }

    public function asString(): string
    {
        return 'Test Runner Configured';
    }
}
