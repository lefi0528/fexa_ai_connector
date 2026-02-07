<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface ErroredSubscriber extends Subscriber
{
    public function notify(Errored $event): void;
}
