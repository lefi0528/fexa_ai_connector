<?php

declare(strict_types=1);

namespace PhpMcp\Client\Enum;


enum ConnectionStatus: string
{
    case Disconnected = 'disconnected'; 
    case Connecting = 'connecting';     
    case Handshaking = 'handshaking';   
    case Ready = 'ready';               
    case Closing = 'closing';         
    case Closed = 'closed';           
    case Error = 'error';             
}
