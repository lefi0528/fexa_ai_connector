<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\Resource;
use PhpMcp\Schema\JsonRpc\Result;
use PhpMcp\Schema\JsonRpc\Response;


class ListResourcesResult extends Result
{
    
    public function __construct(
        public readonly array $resources,
        public readonly ?string $nextCursor = null
    ) {}

    
    public static function make(array $resources, ?string $nextCursor = null): static
    {
        return new static($resources, $nextCursor);
    }

    
    public function toArray(): array
    {
        $result = [
            'resources' => array_map(fn(Resource $r) => $r->toArray(), $this->resources),
        ];

        if ($this->nextCursor !== null) {
            $result['nextCursor'] = $this->nextCursor;
        }

        return $result;
    }

    public static function fromArray(array $data): static
    {
        if (!isset($data['resources']) || !is_array($data['resources'])) {
            throw new \InvalidArgumentException("Missing or invalid 'resources' array in ListResourcesResult data.");
        }

        return new static(
            array_map(fn(array $resource) => Resource::fromArray($resource), $data['resources']),
            $data['nextCursor'] ?? null
        );
    }

    public static function fromResponse(Response $response): static
    {
        return self::fromArray($response->result);
    }
}
