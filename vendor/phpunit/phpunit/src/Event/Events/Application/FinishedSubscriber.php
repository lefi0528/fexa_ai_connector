<?php declare(strict_types=1);

namespace PHPUnit\Event\Application;

use PHPUnit\Event\Subscriber;


interface FinishedSubscriber extends Subscriber
{
    public function notify(Finished $event): void;
}
