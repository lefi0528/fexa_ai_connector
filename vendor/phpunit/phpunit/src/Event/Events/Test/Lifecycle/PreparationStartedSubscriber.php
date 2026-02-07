<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PreparationStartedSubscriber extends Subscriber
{
    public function notify(PreparationStarted $event): void;
}
