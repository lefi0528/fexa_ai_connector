<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class TestProxyCreated implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    
    private readonly string $className;
    private readonly string $constructorArguments;

    
    public function __construct(Telemetry\Info $telemetryInfo, string $className, string $constructorArguments)
    {
        $this->telemetryInfo        = $telemetryInfo;
        $this->className            = $className;
        $this->constructorArguments = $constructorArguments;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    public function constructorArguments(): string
    {
        return $this->constructorArguments;
    }

    public function asString(): string
    {
        return sprintf(
            'Test Proxy Created (%s)',
            $this->className,
        );
    }
}
