<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PreConditionErroredSubscriber extends Subscriber
{
    public function notify(PreConditionErrored $event): void;
}
