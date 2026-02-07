<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface FinishedSubscriber extends Subscriber
{
    public function notify(Finished $event): void;
}
