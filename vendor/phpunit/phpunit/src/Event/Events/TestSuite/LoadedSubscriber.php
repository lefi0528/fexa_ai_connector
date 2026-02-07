<?php declare(strict_types=1);

namespace PHPUnit\Event\TestSuite;

use PHPUnit\Event\Subscriber;


interface LoadedSubscriber extends Subscriber
{
    public function notify(Loaded $event): void;
}
