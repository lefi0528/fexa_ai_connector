<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface MockObjectForTraitCreatedSubscriber extends Subscriber
{
    public function notify(MockObjectForTraitCreated $event): void;
}
