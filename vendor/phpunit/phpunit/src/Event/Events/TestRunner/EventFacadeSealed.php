<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class EventFacadeSealed implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    public function __construct(Telemetry\Info $telemetryInfo)
    {
        $this->telemetryInfo = $telemetryInfo;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function asString(): string
    {
        return 'Event Facade Sealed';
    }
}
