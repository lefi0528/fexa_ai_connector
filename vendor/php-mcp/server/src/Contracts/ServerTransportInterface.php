<?php

declare(strict_types=1);

namespace PhpMcp\Server\Contracts;

use Evenement\EventEmitterInterface;
use PhpMcp\Server\Exception\TransportException;
use PhpMcp\Schema\JsonRpc\Message;
use React\Promise\PromiseInterface;


interface ServerTransportInterface extends EventEmitterInterface
{
    
    public function listen(): void;

    
    public function sendMessage(Message $message, string $sessionId, array $context = []): PromiseInterface;

    
    public function close(): void;
}
