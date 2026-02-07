<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface GarbageCollectionDisabledSubscriber extends Subscriber
{
    public function notify(GarbageCollectionDisabled $event): void;
}
