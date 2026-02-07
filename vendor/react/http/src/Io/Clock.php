<?php

namespace React\Http\Io;

use React\EventLoop\LoopInterface;


class Clock
{
    
    private $loop;

    
    private $now;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    
    public function now()
    {
        if ($this->now === null) {
            $this->now = \microtime(true);

            
            $now =& $this->now;
            $this->loop->futureTick(function () use (&$now) {
                assert($now !== null);
                $now = null;
            });
        }

        return $this->now;
    }
}
