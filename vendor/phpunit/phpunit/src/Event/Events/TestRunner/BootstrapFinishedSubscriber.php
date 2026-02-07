<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface BootstrapFinishedSubscriber extends Subscriber
{
    public function notify(BootstrapFinished $event): void;
}
