<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PhpunitErrorTriggeredSubscriber extends Subscriber
{
    public function notify(PhpunitErrorTriggered $event): void;
}
