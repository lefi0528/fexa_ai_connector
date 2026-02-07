<?php declare(strict_types=1);

namespace PHPUnit\Event;


interface SubscribableDispatcher extends Dispatcher
{
    
    public function registerSubscriber(Subscriber $subscriber): void;

    public function registerTracer(Tracer\Tracer $tracer): void;
}
