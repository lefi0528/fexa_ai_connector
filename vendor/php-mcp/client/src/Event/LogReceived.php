<?php

declare(strict_types=1);

namespace PhpMcp\Client\Event;

final class LogReceived extends AbstractNotificationEvent
{
    
    public function __construct(string $serverName, public readonly array $logData)
    {
        parent::__construct($serverName);
    }
}
