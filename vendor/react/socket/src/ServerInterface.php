<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;


interface ServerInterface extends EventEmitterInterface
{
    
    public function getAddress();

    
    public function pause();

    
    public function resume();

    
    public function close();
}
