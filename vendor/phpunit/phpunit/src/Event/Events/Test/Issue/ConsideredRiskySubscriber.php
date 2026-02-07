<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface ConsideredRiskySubscriber extends Subscriber
{
    public function notify(ConsideredRisky $event): void;
}
