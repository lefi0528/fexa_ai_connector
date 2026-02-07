<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface AfterTestMethodFinishedSubscriber extends Subscriber
{
    public function notify(AfterTestMethodFinished $event): void;
}
