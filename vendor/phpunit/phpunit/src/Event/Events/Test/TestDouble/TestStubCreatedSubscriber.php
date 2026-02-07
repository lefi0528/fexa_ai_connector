<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface TestStubCreatedSubscriber extends Subscriber
{
    public function notify(TestStubCreated $event): void;
}
