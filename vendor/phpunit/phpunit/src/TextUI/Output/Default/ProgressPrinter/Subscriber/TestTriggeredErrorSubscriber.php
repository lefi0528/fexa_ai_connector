<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Output\Default\ProgressPrinter;

use PHPUnit\Event\Test\ErrorTriggered;
use PHPUnit\Event\Test\ErrorTriggeredSubscriber;


final class TestTriggeredErrorSubscriber extends Subscriber implements ErrorTriggeredSubscriber
{
    public function notify(ErrorTriggered $event): void
    {
        $this->printer()->testTriggeredError($event);
    }
}
