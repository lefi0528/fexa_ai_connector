<?php declare(strict_types=1);

namespace PHPUnit\TestRunner\TestResult;

use PHPUnit\Event\TestRunner\WarningTriggered;
use PHPUnit\Event\TestRunner\WarningTriggeredSubscriber;


final class TestRunnerTriggeredWarningSubscriber extends Subscriber implements WarningTriggeredSubscriber
{
    public function notify(WarningTriggered $event): void
    {
        $this->collector()->testRunnerTriggeredWarning($event);
    }
}
