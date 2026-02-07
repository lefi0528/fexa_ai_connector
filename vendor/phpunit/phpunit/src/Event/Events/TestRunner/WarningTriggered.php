<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class WarningTriggered implements Event
{
    private readonly Telemetry\Info $telemetryInfo;
    private readonly string $message;

    public function __construct(Telemetry\Info $telemetryInfo, string $message)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->message       = $message;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function asString(): string
    {
        return sprintf(
            'Test Runner Triggered Warning (%s)',
            $this->message,
        );
    }
}
