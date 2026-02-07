<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\ResourceTemplate;
use PhpMcp\Schema\JsonRpc\Result;
use PhpMcp\Schema\JsonRpc\Response;


class ListResourceTemplatesResult extends Result
{
    
    public function __construct(
        public readonly array $resourceTemplates,
        public readonly ?string $nextCursor = null
    ) {}

    
    public static function make(array $resourceTemplates, ?string $nextCursor = null): static
    {
        return new static($resourceTemplates, $nextCursor);
    }

    
    public function toArray(): array
    {
        $result = [
            'resourceTemplates' => array_map(fn(ResourceTemplate $t) => $t->toArray(), $this->resourceTemplates),
        ];

        if ($this->nextCursor) {
            $result['nextCursor'] = $this->nextCursor;
        }

        return $result;
    }

    public static function fromArray(array $data): static
    {
        if (!isset($data['resourceTemplates']) || !is_array($data['resourceTemplates'])) {
            throw new \InvalidArgumentException("Missing or invalid 'resourceTemplates' array in ListResourceTemplatesResult data.");
        }

        return new static(
            array_map(fn(array $resourceTemplate) => ResourceTemplate::fromArray($resourceTemplate), $data['resourceTemplates']),
            $data['nextCursor'] ?? null
        );
    }

    public static function fromResponse(Response $response): static
    {
        return self::fromArray($response->result);
    }
}
