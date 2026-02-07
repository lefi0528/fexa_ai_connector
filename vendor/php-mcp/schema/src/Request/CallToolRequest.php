<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Request;

use PhpMcp\Schema\Constants;
use PhpMcp\Schema\JsonRpc\Request;


class CallToolRequest extends Request
{
    
    public function __construct(
        string|int $id,
        public readonly string $name,
        public readonly array $arguments,
        public readonly ?array $_meta = null
    ) {
        $params = [
            'name' => $name,
            'arguments' => (object) $arguments,
        ];

        if ($_meta !== null) {
            $params['_meta'] = $_meta;
        }

        parent::__construct(Constants::JSONRPC_VERSION, $id, 'tools/call', $params);
    }

    
    public static function make(string|int $id, string $name, array $arguments, ?array $_meta = null): static
    {
        return new static($id, $name, $arguments, $_meta);
    }

    public static function fromRequest(Request $request): static
    {
        if ($request->method !== 'tools/call') {
            throw new \InvalidArgumentException('Request is not a call tool request');
        }

        $params = $request->params ?? [];

        if (!isset($params['name']) || !is_string($params['name'])) {
            throw new \InvalidArgumentException("Missing or invalid 'name' parameter for tools/call.");
        }

        $arguments = $params['arguments'] ?? [];

        if ($arguments instanceof \stdClass) {
            $arguments = (array) $arguments;
        }

        if (!is_array($arguments)) {
            throw new \InvalidArgumentException("Parameter 'arguments' must be an array.");
        }

        return new static(
            $request->id,
            $params['name'],
            $arguments,
            $params['_meta'] ?? null
        );
    }
}
