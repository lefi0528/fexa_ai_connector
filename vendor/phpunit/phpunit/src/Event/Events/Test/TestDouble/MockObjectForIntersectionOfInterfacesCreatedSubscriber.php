<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface MockObjectForIntersectionOfInterfacesCreatedSubscriber extends Subscriber
{
    public function notify(MockObjectForIntersectionOfInterfacesCreated $event): void;
}
