<?php declare(strict_types=1);

namespace PHPUnit\TestRunner\TestResult;

use PHPUnit\Event\Test\PhpDeprecationTriggered;
use PHPUnit\Event\Test\PhpDeprecationTriggeredSubscriber;


final class TestTriggeredPhpDeprecationSubscriber extends Subscriber implements PhpDeprecationTriggeredSubscriber
{
    public function notify(PhpDeprecationTriggered $event): void
    {
        $this->collector()->testTriggeredPhpDeprecation($event);
    }
}
