<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface BeforeFirstTestMethodCalledSubscriber extends Subscriber
{
    public function notify(BeforeFirstTestMethodCalled $event): void;
}
