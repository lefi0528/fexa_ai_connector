<?php declare(strict_types=1);

namespace PHPUnit\Runner\Baseline;

use PHPUnit\Event\Test\WarningTriggered;
use PHPUnit\Event\Test\WarningTriggeredSubscriber;
use PHPUnit\Runner\FileDoesNotExistException;


final class TestTriggeredWarningSubscriber extends Subscriber implements WarningTriggeredSubscriber
{
    
    public function notify(WarningTriggered $event): void
    {
        $this->generator()->testTriggeredIssue($event);
    }
}
