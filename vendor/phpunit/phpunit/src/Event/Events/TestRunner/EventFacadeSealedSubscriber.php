<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface EventFacadeSealedSubscriber extends Subscriber
{
    public function notify(EventFacadeSealed $event): void;
}
