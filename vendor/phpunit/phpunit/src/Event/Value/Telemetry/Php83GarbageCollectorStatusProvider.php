<?php declare(strict_types=1);

namespace PHPUnit\Event\Telemetry;

use function gc_status;


final class Php83GarbageCollectorStatusProvider implements GarbageCollectorStatusProvider
{
    public function status(): GarbageCollectorStatus
    {
        $status = gc_status();

        return new GarbageCollectorStatus(
            $status['runs'],
            $status['collected'],
            $status['threshold'],
            $status['roots'],
            $status['application_time'],
            $status['collector_time'],
            $status['destructor_time'],
            $status['free_time'],
            $status['running'],
            $status['protected'],
            $status['full'],
            $status['buffer_size'],
        );
    }
}
