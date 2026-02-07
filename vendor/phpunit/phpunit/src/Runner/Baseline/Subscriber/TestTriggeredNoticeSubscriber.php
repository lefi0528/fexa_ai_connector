<?php declare(strict_types=1);

namespace PHPUnit\Runner\Baseline;

use PHPUnit\Event\Test\NoticeTriggered;
use PHPUnit\Event\Test\NoticeTriggeredSubscriber;
use PHPUnit\Runner\FileDoesNotExistException;


final class TestTriggeredNoticeSubscriber extends Subscriber implements NoticeTriggeredSubscriber
{
    
    public function notify(NoticeTriggered $event): void
    {
        $this->generator()->testTriggeredIssue($event);
    }
}
