<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class MockObjectForAbstractClassCreated implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    
    private readonly string $className;

    
    public function __construct(Telemetry\Info $telemetryInfo, string $className)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->className     = $className;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    public function asString(): string
    {
        return sprintf(
            'Mock Object Created (%s)',
            $this->className,
        );
    }
}
