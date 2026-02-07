<?php

namespace PhpMcp\Client\JsonRpc\Results;

use PhpMcp\Client\JsonRpc\Result;
use PhpMcp\Client\Model\Capabilities;

class InitializeResult extends Result
{
    
    public function __construct(
        public readonly string $serverName,
        public readonly string $serverVersion,
        public readonly string $protocolVersion,
        public readonly Capabilities $capabilities,
        public readonly ?string $instructions = null
    ) {}

    public static function fromArray(array $data): static
    {
        $serverInfo = $data['serverInfo'] ?? [];
        $capabilities = Capabilities::fromServerResponse($data['capabilities']);

        return new static(
            serverName: $serverInfo['name'] ?? 'Unknown Server',
            serverVersion: $serverInfo['version'] ?? 'Unknown Version',
            protocolVersion: $data['protocolVersion'],
            capabilities: $capabilities,
            instructions: $data['instructions'] ?? null
        );
    }

    
    public function toArray(): array
    {
        $result = [
            'serverInfo' => [
                'name' => $this->serverName,
                'version' => $this->serverVersion,
            ],
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities,
        ];

        if ($this->instructions !== null) {
            $result['instructions'] = $this->instructions;
        }

        return $result;
    }
}
