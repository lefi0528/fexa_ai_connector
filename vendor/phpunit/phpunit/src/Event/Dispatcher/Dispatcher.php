<?php declare(strict_types=1);

namespace PHPUnit\Event;


interface Dispatcher
{
    
    public function dispatch(Event $event): void;
}
