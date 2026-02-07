<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PreConditionCalledSubscriber extends Subscriber
{
    public function notify(PreConditionCalled $event): void;
}
