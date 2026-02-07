<?php

namespace React\EventLoop;

interface TimerInterface
{
    
    public function getInterval();

    
    public function getCallback();

    
    public function isPeriodic();
}
