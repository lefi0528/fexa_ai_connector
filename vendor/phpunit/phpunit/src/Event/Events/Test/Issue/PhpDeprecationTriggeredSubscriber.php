<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PhpDeprecationTriggeredSubscriber extends Subscriber
{
    public function notify(PhpDeprecationTriggered $event): void;
}
