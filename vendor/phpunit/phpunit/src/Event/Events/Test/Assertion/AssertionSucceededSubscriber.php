<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface AssertionSucceededSubscriber extends Subscriber
{
    public function notify(AssertionSucceeded $event): void;
}
