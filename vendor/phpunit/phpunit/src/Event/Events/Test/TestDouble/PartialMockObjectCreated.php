<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class PartialMockObjectCreated implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    
    private readonly string $className;

    
    private readonly array $methodNames;

    
    public function __construct(Telemetry\Info $telemetryInfo, string $className, string ...$methodNames)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->className     = $className;
        $this->methodNames   = $methodNames;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function methodNames(): array
    {
        return $this->methodNames;
    }

    public function asString(): string
    {
        return sprintf(
            'Partial Mock Object Created (%s)',
            $this->className,
        );
    }
}
