<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface MockObjectCreatedSubscriber extends Subscriber
{
    public function notify(MockObjectCreated $event): void;
}
