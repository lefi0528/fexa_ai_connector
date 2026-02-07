<?php

declare(strict_types=1);

namespace PhpMcp\Client\Contracts;

use Evenement\EventEmitterInterface;
use PhpMcp\Client\JsonRpc\Message;
use React\Promise\PromiseInterface;


interface TransportInterface extends EventEmitterInterface
{
    
    public function connect(): PromiseInterface;

    
    public function send(Message $message): PromiseInterface;

    
    public function close(): void;
}
