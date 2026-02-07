<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\Content\AudioContent;
use PhpMcp\Schema\Content\Content;
use PhpMcp\Schema\Content\EmbeddedResource;
use PhpMcp\Schema\Content\ImageContent;
use PhpMcp\Schema\Content\TextContent;
use PhpMcp\Schema\JsonRpc\Result;
use PhpMcp\Schema\JsonRpc\Response;


class CallToolResult extends Result
{
    
    public function __construct(
        public readonly array $content,
        public readonly bool $isError = false
    ) {
        foreach ($this->content as $item) {
            if (!$item instanceof Content) {
                throw new \InvalidArgumentException('Content must be an array of Content objects.');
            }
        }
    }

    
    public static function make(array $content, bool $isError = false): static
    {
        return new static($content, $isError);
    }

    
    public static function success(array $content): static
    {
        return new static($content, false);
    }

    
    public static function error(array $content): static
    {
        return new static($content, true);
    }

    
    public function toArray(): array
    {
        return [
            'content' => array_map(fn($item) => $item->toArray(), $this->content),
            'isError' => $this->isError,
        ];
    }

    public static function fromArray(array $data): static
    {
        if (!isset($data['content']) || !is_array($data['content'])) {
            throw new \InvalidArgumentException("Missing or invalid 'content' array in CallToolResult data.");
        }

        $contents = [];

        foreach ($data['content'] as $item) {
            $contents[] = match ($item['type'] ?? null) {
                'text' => TextContent::fromArray($item),
                'image' => ImageContent::fromArray($item),
                'audio' => AudioContent::fromArray($item),
                'resource' => EmbeddedResource::fromArray($item),
                null => throw new \InvalidArgumentException("Missing 'type' in CallToolResult content item."),
                default => throw new \InvalidArgumentException("Invalid content type in CallToolResult data: {$item['type']}"),
            };
        }

        return new static(
            $contents,
            $data['isError'] ?? false
        );
    }

    public static function fromResponse(Response $response): static
    {
        return self::fromArray($response->result);
    }
}
