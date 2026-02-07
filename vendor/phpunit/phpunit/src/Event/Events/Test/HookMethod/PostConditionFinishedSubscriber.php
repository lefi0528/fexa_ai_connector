<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Subscriber;


interface PostConditionFinishedSubscriber extends Subscriber
{
    public function notify(PostConditionFinished $event): void;
}
