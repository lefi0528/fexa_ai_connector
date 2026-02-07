<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface ExecutionFinishedSubscriber extends Subscriber
{
    public function notify(ExecutionFinished $event): void;
}
