<?php declare(strict_types=1);

namespace PHPUnit\Event\TestSuite;

use PHPUnit\Event\Subscriber;


interface StartedSubscriber extends Subscriber
{
    public function notify(Started $event): void;
}
