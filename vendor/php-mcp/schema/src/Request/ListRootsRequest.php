<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Request;

use PhpMcp\Schema\Constants;
use PhpMcp\Schema\JsonRpc\Request;


class ListRootsRequest extends Request
{
    public function __construct(
        string|int $id,
        public readonly ?array $_meta = null
    ) {
        $params = [];
        if ($_meta !== null) {
            $params['_meta'] = $_meta;
        }

        parent::__construct(Constants::JSONRPC_VERSION, $id, 'roots/list', $params);
    }

    
    public static function make(string|int $id, ?array $_meta = null): static
    {
        return new static($id, $_meta);
    }
}
