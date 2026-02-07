<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface GarbageCollectionEnabledSubscriber extends Subscriber
{
    public function notify(GarbageCollectionEnabled $event): void;
}
