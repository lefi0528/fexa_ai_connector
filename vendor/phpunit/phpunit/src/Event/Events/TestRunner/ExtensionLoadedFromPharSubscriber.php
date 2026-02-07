<?php declare(strict_types=1);

namespace PHPUnit\Event\TestRunner;

use PHPUnit\Event\Subscriber;


interface ExtensionLoadedFromPharSubscriber extends Subscriber
{
    public function notify(ExtensionLoadedFromPhar $event): void;
}
