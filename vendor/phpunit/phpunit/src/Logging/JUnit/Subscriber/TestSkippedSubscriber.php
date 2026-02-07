<?php declare(strict_types=1);

namespace PHPUnit\Logging\JUnit;

use PHPUnit\Event\InvalidArgumentException;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;


final class TestSkippedSubscriber extends Subscriber implements SkippedSubscriber
{
    
    public function notify(Skipped $event): void
    {
        $this->logger()->testSkipped($event);
    }
}
