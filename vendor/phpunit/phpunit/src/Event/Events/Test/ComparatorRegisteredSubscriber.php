<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface ComparatorRegisteredSubscriber extends Subscriber
{
    public function notify(ComparatorRegistered $event): void;
}
