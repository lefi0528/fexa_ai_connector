<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface ExtensionBootstrappedSubscriber extends Subscriber
{
    public function notify(ExtensionBootstrapped $event): void;
}
