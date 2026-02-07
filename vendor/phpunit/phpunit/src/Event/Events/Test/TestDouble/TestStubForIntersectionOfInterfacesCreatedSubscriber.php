<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface TestStubForIntersectionOfInterfacesCreatedSubscriber extends Subscriber
{
    public function notify(TestStubForIntersectionOfInterfacesCreated $event): void;
}
