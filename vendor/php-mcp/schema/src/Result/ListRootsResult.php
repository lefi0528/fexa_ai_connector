<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\Root;
use PhpMcp\Schema\JsonRpc\Result;


class ListRootsResult extends Result
{
    
    public function __construct(
        public readonly array $roots,
        public readonly ?array $_meta = null
    ) {
    }

    
    public static function make(array $roots, ?array $_meta = null): static
    {
        return new static($roots, $_meta);
    }

    public function toArray(): array
    {
        $result = [
            'roots' => $this->roots,
        ];

        if ($this->_meta !== null) {
            $result['_meta'] = $this->_meta;
        }

        return $result;
    }
}
