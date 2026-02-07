<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface ExecutionAbortedSubscriber extends Subscriber
{
    public function notify(ExecutionAborted $event): void;
}
