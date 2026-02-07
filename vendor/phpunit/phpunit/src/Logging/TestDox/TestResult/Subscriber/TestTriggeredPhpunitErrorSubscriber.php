<?php declare(strict_types=1);

namespace PHPUnit\Logging\TestDox;

use PHPUnit\Event\Test\PhpunitErrorTriggered;
use PHPUnit\Event\Test\PhpunitErrorTriggeredSubscriber;


final class TestTriggeredPhpunitErrorSubscriber extends Subscriber implements PhpunitErrorTriggeredSubscriber
{
    public function notify(PhpunitErrorTriggered $event): void
    {
        $this->collector()->testTriggeredPhpunitError($event);
    }
}
