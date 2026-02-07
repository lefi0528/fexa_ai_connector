<?php declare(strict_types=1);

namespace PHPUnit\Event\Telemetry;


interface MemoryMeter
{
    public function memoryUsage(): MemoryUsage;

    public function peakMemoryUsage(): MemoryUsage;
}
