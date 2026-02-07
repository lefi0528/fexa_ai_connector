<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class MockObjectFromWsdlCreated implements Event
{
    private readonly Telemetry\Info $telemetryInfo;
    private readonly string $wsdlFile;

    
    private readonly string $originalClassName;

    
    private readonly string $mockClassName;

    
    private readonly array $methods;
    private readonly bool $callOriginalConstructor;
    private readonly array $options;

    
    public function __construct(Telemetry\Info $telemetryInfo, string $wsdlFile, string $originalClassName, string $mockClassName, array $methods, bool $callOriginalConstructor, array $options)
    {
        $this->telemetryInfo           = $telemetryInfo;
        $this->wsdlFile                = $wsdlFile;
        $this->originalClassName       = $originalClassName;
        $this->mockClassName           = $mockClassName;
        $this->methods                 = $methods;
        $this->callOriginalConstructor = $callOriginalConstructor;
        $this->options                 = $options;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function wsdlFile(): string
    {
        return $this->wsdlFile;
    }

    
    public function originalClassName(): string
    {
        return $this->originalClassName;
    }

    
    public function mockClassName(): string
    {
        return $this->mockClassName;
    }

    
    public function methods(): array
    {
        return $this->methods;
    }

    public function callOriginalConstructor(): bool
    {
        return $this->callOriginalConstructor;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function asString(): string
    {
        return sprintf(
            'Mock Object Created (%s)',
            $this->wsdlFile,
        );
    }
}
