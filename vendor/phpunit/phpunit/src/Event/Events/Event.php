<?php declare(strict_types=1);

namespace PHPUnit\Event;


interface Event
{
    public function telemetryInfo(): Telemetry\Info;

    public function asString(): string;
}
