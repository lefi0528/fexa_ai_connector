<?php declare(strict_types=1);

namespace PHPUnit\Event\Tracer;

use PHPUnit\Event\Event;


interface Tracer
{
    public function trace(Event $event): void;
}
