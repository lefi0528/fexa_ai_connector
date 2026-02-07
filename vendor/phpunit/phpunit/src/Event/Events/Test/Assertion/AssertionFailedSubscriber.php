<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface AssertionFailedSubscriber extends Subscriber
{
    public function notify(AssertionFailed $event): void;
}
