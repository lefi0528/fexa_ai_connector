<?php declare(strict_types=1);

namespace PHPUnit\Event\TestSuite;

use PHPUnit\Event\Subscriber;


interface SortedSubscriber extends Subscriber
{
    public function notify(Sorted $event): void;
}
