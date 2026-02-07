<?php declare(strict_types=1);

namespace PHPUnit\Event\Telemetry;


interface GarbageCollectorStatusProvider
{
    public function status(): GarbageCollectorStatus;
}
