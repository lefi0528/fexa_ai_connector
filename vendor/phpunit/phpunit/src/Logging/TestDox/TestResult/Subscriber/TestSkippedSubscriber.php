<?php declare(strict_types=1);

namespace PHPUnit\Logging\TestDox;

use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;


final class TestSkippedSubscriber extends Subscriber implements SkippedSubscriber
{
    public function notify(Skipped $event): void
    {
        $this->collector()->testSkipped($event);
    }
}
