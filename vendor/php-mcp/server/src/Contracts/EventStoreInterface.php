<?php

declare(strict_types=1);

namespace PhpMcp\Server\Contracts;


interface EventStoreInterface
{
    
    public function storeEvent(string $streamId, string $message): string;

    
    public function replayEventsAfter(string $lastEventId, callable $sendCallback): void;
}
