<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface GarbageCollectionTriggeredSubscriber extends Subscriber
{
    public function notify(GarbageCollectionTriggered $event): void;
}
