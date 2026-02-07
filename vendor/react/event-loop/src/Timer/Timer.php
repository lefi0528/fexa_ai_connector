<?php

namespace React\EventLoop\Timer;

use React\EventLoop\TimerInterface;


final class Timer implements TimerInterface
{
    const MIN_INTERVAL = 0.000001;

    private $interval;
    private $callback;
    private $periodic;

    
    public function __construct($interval, $callback, $periodic = false)
    {
        if ($interval < self::MIN_INTERVAL) {
            $interval = self::MIN_INTERVAL;
        }

        $this->interval = (float) $interval;
        $this->callback = $callback;
        $this->periodic = (bool) $periodic;
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function isPeriodic()
    {
        return $this->periodic;
    }
}
