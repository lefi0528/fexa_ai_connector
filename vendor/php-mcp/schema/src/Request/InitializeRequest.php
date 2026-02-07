<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Request;

use PhpMcp\Schema\ClientCapabilities;
use PhpMcp\Schema\Constants;
use PhpMcp\Schema\Implementation;
use PhpMcp\Schema\JsonRpc\Request;


class InitializeRequest extends Request
{
    
    public function __construct(
        string|int $id,
        public readonly string $protocolVersion,
        public readonly ClientCapabilities $capabilities,
        public readonly Implementation $clientInfo,
        public readonly ?array $_meta = null
    ) {
        $params = [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities->toArray(),
            'clientInfo' => $this->clientInfo->toArray(),
        ];

        if ($this->_meta !== null) {
            $params['_meta'] = $this->_meta;
        }

        parent::__construct(Constants::JSONRPC_VERSION, $id, 'initialize', $params);
    }

    
    public static function make(string|int $id, string $protocolVersion, ClientCapabilities $capabilities, Implementation $clientInfo, ?array $_meta = null): static
    {
        return new static($id, $protocolVersion, $capabilities, $clientInfo, $_meta);
    }

    public static function fromRequest(Request $request): static
    {
        if ($request->method !== 'initialize') {
            throw new \InvalidArgumentException('Request is not an initialize request');
        }

        $params = $request->params;

        if (! isset($params['protocolVersion'])) {
            throw new \InvalidArgumentException('protocolVersion is required');
        }

        if (! isset($params['capabilities'])) {
            throw new \InvalidArgumentException('capabilities is required');
        }
        $capabilities = ClientCapabilities::fromArray($params['capabilities']);


        if (! isset($params['clientInfo'])) {
            throw new \InvalidArgumentException('clientInfo is required');
        }
        $clientInfo = Implementation::fromArray($params['clientInfo']);

        return new static($request->id, $params['protocolVersion'], $capabilities, $clientInfo, $params['_meta'] ?? null);
    }
}
