<?php declare(strict_types=1);

namespace PHPUnit\Event\Telemetry;

use function hrtime;
use PHPUnit\Event\InvalidArgumentException;


final class SystemStopWatch implements StopWatch
{
    
    public function current(): HRTime
    {
        return HRTime::fromSecondsAndNanoseconds(...hrtime());
    }
}
