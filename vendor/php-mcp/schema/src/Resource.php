<?php

declare(strict_types=1);

namespace PhpMcp\Schema;

use JsonSerializable;


class Resource implements JsonSerializable
{
    
    private const RESOURCE_NAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';

    
    private const URI_PATTERN = '/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\/[^\s]*$/';

    
    public function __construct(
        public readonly string $uri,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $mimeType = null,
        public readonly ?Annotations $annotations = null,
        public readonly ?int $size = null
    ) {
        if (!preg_match(self::RESOURCE_NAME_PATTERN, $name)) {
            throw new \InvalidArgumentException("Invalid resource name: must contain only alphanumeric characters, underscores, and hyphens.");
        }
        if (!preg_match(self::URI_PATTERN, $uri)) {
            throw new \InvalidArgumentException("Invalid resource URI: must be a valid URI with a scheme and optional path.");
        }
    }

    
    public static function make(string $uri, string $name, ?string $description = null, ?string $mimeType = null, ?Annotations $annotations = null, ?int $size = null): static
    {
        return new static($uri, $name, $description, $mimeType, $annotations, $size);
    }

    public function toArray(): array
    {
        $data = [
            'uri' => $this->uri,
            'name' => $this->name,
        ];
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }
        if ($this->annotations !== null) {
            $data['annotations'] = $this->annotations->toArray();
        }
        if ($this->size !== null) {
            $data['size'] = $this->size;
        }
        return $data;
    }

    public static function fromArray(array $data): static
    {
        if (empty($data['uri']) || !is_string($data['uri'])) {
            throw new \InvalidArgumentException("Invalid or missing 'uri' in Resource data.");
        }
        if (empty($data['name']) || !is_string($data['name'])) {
            throw new \InvalidArgumentException("Invalid or missing 'name' in Resource data.");
        }
        return new static(
            uri: $data['uri'],
            name: $data['name'],
            description: $data['description'] ?? null,
            mimeType: $data['mimeType'] ?? null,
            annotations: isset($data['annotations']) ? Annotations::fromArray($data['annotations']) : null,
            size: isset($data['size']) ? (int)$data['size'] : null
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
