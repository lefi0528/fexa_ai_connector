<?php

namespace React\EventLoop\Tick;

use SplQueue;


final class FutureTickQueue
{
    private $queue;

    public function __construct()
    {
        $this->queue = new SplQueue();
    }

    
    public function add($listener)
    {
        $this->queue->enqueue($listener);
    }

    
    public function tick()
    {
        
        $count = $this->queue->count();

        while ($count--) {
            \call_user_func(
                $this->queue->dequeue()
            );
        }
    }

    
    public function isEmpty()
    {
        return $this->queue->isEmpty();
    }
}
