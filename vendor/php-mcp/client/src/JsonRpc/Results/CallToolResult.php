<?php

namespace PhpMcp\Client\JsonRpc\Results;

use PhpMcp\Client\JsonRpc\Result;
use PhpMcp\Client\Model\Content\ContentFactory;
use PhpMcp\Client\Model\Content\EmbeddedResource;
use PhpMcp\Client\Model\Content\ImageContent;
use PhpMcp\Client\Model\Content\TextContent;

class CallToolResult extends Result
{
    
    public function __construct(
        public readonly array $content,
        public readonly bool $isError = false
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            content: array_map(fn (array $contentData) => ContentFactory::createFromArray($contentData), $data['content']),
            isError: $data['isError'] ?? false
        );
    }

    
    public function isError(): bool
    {
        return $this->isError;
    }

    public function isSuccess(): bool
    {
        return ! $this->isError;
    }

    
    public function toArray(): array
    {
        return [
            'content' => array_map(fn ($item) => $item->toArray(), $this->content),
            'isError' => $this->isError,
        ];
    }
}
