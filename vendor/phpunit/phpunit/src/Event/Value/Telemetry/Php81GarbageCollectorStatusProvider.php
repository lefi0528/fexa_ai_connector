<?php declare(strict_types=1);

namespace PHPUnit\Event\Telemetry;

use function gc_status;


final class Php81GarbageCollectorStatusProvider implements GarbageCollectorStatusProvider
{
    public function status(): GarbageCollectorStatus
    {
        $status = gc_status();

        return new GarbageCollectorStatus(
            $status['runs'],
            $status['collected'],
            $status['threshold'],
            $status['roots'],
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );
    }
}
