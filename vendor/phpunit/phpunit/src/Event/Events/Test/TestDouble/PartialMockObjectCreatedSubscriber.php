<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PartialMockObjectCreatedSubscriber extends Subscriber
{
    public function notify(PartialMockObjectCreated $event): void;
}
