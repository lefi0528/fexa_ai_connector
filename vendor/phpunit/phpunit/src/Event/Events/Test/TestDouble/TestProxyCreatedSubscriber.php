<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface TestProxyCreatedSubscriber extends Subscriber
{
    public function notify(TestProxyCreated $event): void;
}
