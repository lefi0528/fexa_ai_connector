<?php

namespace React\Stream;

use Evenement\EventEmitterInterface;


interface WritableStreamInterface extends EventEmitterInterface
{
    
    public function isWritable();

    
    public function write($data);

    
    public function end($data = null);

    
    public function close();
}
