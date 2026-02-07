<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\Implementation;
use PhpMcp\Schema\JsonRpc\Result;
use PhpMcp\Schema\JsonRpc\Response;
use PhpMcp\Schema\ServerCapabilities;


class InitializeResult extends Result
{
    
    public function __construct(
        public readonly string $protocolVersion,
        public readonly ServerCapabilities $capabilities,
        public readonly Implementation $serverInfo,
        public readonly ?string $instructions = null,
        public readonly ?array $_meta = null
    ) {}

    
    public function toArray(): array
    {
        $data = [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities->toArray(),
            'serverInfo' => $this->serverInfo->toArray(),
        ];
        if ($this->instructions !== null) {
            $data['instructions'] = $this->instructions;
        }
        if ($this->_meta !== null) {
            $data['_meta'] = $this->_meta;
        }
        return $data;
    }

    
    public static function make(string $protocolVersion, ServerCapabilities $capabilities, Implementation $serverInfo, ?string $instructions = null, ?array $_meta = null): static
    {
        return new static($protocolVersion, $capabilities, $serverInfo, $instructions, $_meta);
    }

    public static function fromArray(array $data): static
    {
        if (!isset($data['protocolVersion']) || !is_string($data['protocolVersion'])) {
            throw new \InvalidArgumentException("Missing or invalid 'protocolVersion'");
        }
        if (!isset($data['capabilities']) || !is_array($data['capabilities'])) {
            throw new \InvalidArgumentException("Missing or invalid 'capabilities'");
        }
        if (!isset($data['serverInfo']) || !is_array($data['serverInfo'])) {
            throw new \InvalidArgumentException("Missing or invalid 'serverInfo'");
        }

        return new static(
            $data['protocolVersion'],
            ServerCapabilities::fromArray($data['capabilities']),
            Implementation::fromArray($data['serverInfo']),
            $data['instructions'] ?? null,
            $data['_meta'] ?? null
        );
    }

    public static function fromResponse(Response $response): static
    {
        return self::fromArray($response->result);
    }
}
