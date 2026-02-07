<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Request;

use PhpMcp\Schema\Constants;
use PhpMcp\Schema\Enum\LoggingLevel;
use PhpMcp\Schema\JsonRpc\Request;


class SetLogLevelRequest extends Request
{
    
    public function __construct(
        string|int $id,
        public readonly LoggingLevel $level,
        public readonly ?array $_meta = null
    ) {
        $params = [
            'level' => $level->value,
        ];

        if ($_meta !== null) {
            $params['_meta'] = $_meta;
        }

        parent::__construct(Constants::JSONRPC_VERSION, $id, 'logging/setLevel', $params);
    }

    
    public static function make(string|int $id, LoggingLevel $level, ?array $_meta = null): static
    {
        return new static($id, $level, $_meta);
    }

    public static function fromRequest(Request $request): static
    {
        if ($request->method !== 'logging/setLevel') {
            throw new \InvalidArgumentException('Request is not a logging/setLevel request');
        }

        $params = $request->params;

        if (! isset($params['level']) || ! is_string($params['level']) || empty($params['level'])) {
            throw new \InvalidArgumentException("Missing or invalid 'level' parameter for logging/setLevel.");
        }

        return new static($request->id, LoggingLevel::from($params['level']), $params['_meta'] ?? null);
    }
}
