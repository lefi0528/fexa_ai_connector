<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use const PHP_EOL;
use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class PrintedUnexpectedOutput implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    
    private readonly string $output;

    
    public function __construct(Telemetry\Info $telemetryInfo, string $output)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->output        = $output;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    
    public function output(): string
    {
        return $this->output;
    }

    public function asString(): string
    {
        return sprintf(
            'Test Printed Unexpected Output%s%s',
            PHP_EOL,
            $this->output,
        );
    }
}
