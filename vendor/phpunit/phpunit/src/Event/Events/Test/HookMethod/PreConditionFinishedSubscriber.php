<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PreConditionFinishedSubscriber extends Subscriber
{
    public function notify(PreConditionFinished $event): void;
}
