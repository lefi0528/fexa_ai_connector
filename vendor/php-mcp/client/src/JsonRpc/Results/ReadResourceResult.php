<?php

namespace PhpMcp\Client\JsonRpc\Results;

use PhpMcp\Client\Exception\ProtocolException;
use PhpMcp\Client\JsonRpc\Result;
use PhpMcp\Client\Model\Content\EmbeddedResource;

class ReadResourceResult extends Result
{
    
    public function __construct(
        public readonly array $contents
    ) {}

    public static function fromArray(array $data): static
    {
        if (! isset($data['contents']) || ! is_array($data['contents'])) {
            throw new ProtocolException("Missing or invalid 'contents' array in ReadResourceResult.");
        }

        $contents = [];
        foreach ($data['contents'] as $contentData) {
            $contents[] = EmbeddedResource::fromArray($contentData);
        }

        return new static($contents);
    }

    
    public function toArray(): array
    {
        return [
            'contents' => array_map(fn ($resource) => $resource->toArray(), $this->contents),
        ];
    }
}
