<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PassedSubscriber extends Subscriber
{
    public function notify(Passed $event): void;
}
