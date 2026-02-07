<?php

namespace PhpMcp\Client\JsonRpc\Results;

use PhpMcp\Client\JsonRpc\Result;
use PhpMcp\Client\Model\Definitions\ResourceDefinition;

class ListResourcesResult extends Result
{
    
    public function __construct(
        public readonly array $resources,
        public readonly ?string $nextCursor = null
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            resources: array_map(fn (array $resourceData) => ResourceDefinition::fromArray($resourceData), $data['resources']),
            nextCursor: $data['nextCursor'] ?? null
        );
    }

    
    public function toArray(): array
    {
        $result = [
            'resources' => array_map(fn (ResourceDefinition $r) => $r->toArray(), $this->resources),
        ];

        if ($this->nextCursor !== null) {
            $result['nextCursor'] = $this->nextCursor;
        }

        return $result;
    }
}
