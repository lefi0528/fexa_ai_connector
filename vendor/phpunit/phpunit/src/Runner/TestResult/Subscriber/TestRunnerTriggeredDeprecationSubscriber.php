<?php declare(strict_types=1);

namespace PHPUnit\TestRunner\TestResult;

use PHPUnit\Event\TestRunner\DeprecationTriggered;
use PHPUnit\Event\TestRunner\DeprecationTriggeredSubscriber;


final class TestRunnerTriggeredDeprecationSubscriber extends Subscriber implements DeprecationTriggeredSubscriber
{
    public function notify(DeprecationTriggered $event): void
    {
        $this->collector()->testRunnerTriggeredDeprecation($event);
    }
}
