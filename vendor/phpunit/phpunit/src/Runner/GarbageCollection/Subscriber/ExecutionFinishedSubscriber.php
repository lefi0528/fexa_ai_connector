<?php declare(strict_types=1);

namespace PHPUnit\Runner\GarbageCollection;

use PHPUnit\Event\InvalidArgumentException;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber as TestRunnerExecutionFinishedSubscriber;


final class ExecutionFinishedSubscriber extends Subscriber implements TestRunnerExecutionFinishedSubscriber
{
    
    public function notify(ExecutionFinished $event): void
    {
        $this->handler()->executionFinished();
    }
}
