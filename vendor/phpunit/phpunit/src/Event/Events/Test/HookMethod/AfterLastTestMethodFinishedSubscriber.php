<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface AfterLastTestMethodFinishedSubscriber extends Subscriber
{
    public function notify(AfterLastTestMethodFinished $event): void;
}
