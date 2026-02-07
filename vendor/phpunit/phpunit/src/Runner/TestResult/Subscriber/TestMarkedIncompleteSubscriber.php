<?php declare(strict_types=1);

namespace PHPUnit\TestRunner\TestResult;

use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\MarkedIncompleteSubscriber;


final class TestMarkedIncompleteSubscriber extends Subscriber implements MarkedIncompleteSubscriber
{
    public function notify(MarkedIncomplete $event): void
    {
        $this->collector()->testMarkedIncomplete($event);
    }
}
