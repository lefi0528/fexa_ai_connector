<?php declare(strict_types=1);

namespace PHPUnit\Event\Telemetry;


interface StopWatch
{
    public function current(): HRTime;
}
