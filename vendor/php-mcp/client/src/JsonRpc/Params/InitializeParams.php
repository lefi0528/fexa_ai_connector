<?php

declare(strict_types=1);

namespace PhpMcp\Client\JsonRpc\Params;

use PhpMcp\Client\Model\Capabilities;

class InitializeParams
{
    public function __construct(
        public readonly string $clientName,
        public readonly string $clientVersion,
        public readonly string $protocolVersion,
        public readonly Capabilities $capabilities,

        
    ) {}

    public function toArray(): array
    {
        
        return [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities->toClientArray(), 
            'clientInfo' => [
                'name' => $this->clientName,
                'version' => $this->clientVersion,
            ],
        ];
    }
}
