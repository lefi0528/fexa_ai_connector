<?php declare(strict_types=1);

namespace PHPUnit\Runner\Baseline;

use PHPUnit\Event\Test\PhpNoticeTriggered;
use PHPUnit\Event\Test\PhpNoticeTriggeredSubscriber;
use PHPUnit\Runner\FileDoesNotExistException;


final class TestTriggeredPhpNoticeSubscriber extends Subscriber implements PhpNoticeTriggeredSubscriber
{
    
    public function notify(PhpNoticeTriggered $event): void
    {
        $this->generator()->testTriggeredIssue($event);
    }
}
