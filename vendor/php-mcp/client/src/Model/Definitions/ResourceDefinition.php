<?php

namespace PhpMcp\Client\Model\Definitions;

use PhpMcp\Client\Exception\ProtocolException;


class ResourceDefinition
{
    
    private const RESOURCE_NAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';

    
    private const URI_PATTERN = '/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\/[^\s]*$/';

    
    public function __construct(
        public readonly string $uri,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $mimeType,
        public readonly ?int $size,
        public readonly array $annotations = []
    ) {
        $this->validate();
    }

    
    private function validate(): void
    {
        if (! preg_match(self::URI_PATTERN, $this->uri)) {
            throw new ProtocolException(
                "Resource URI '{$this->uri}' is invalid. URIs must match the pattern ".self::URI_PATTERN
                .' (valid scheme followed by :// and optional path).'
            );
        }

        if (! preg_match(self::RESOURCE_NAME_PATTERN, $this->name)) {
            throw new ProtocolException(
                "Resource name '{$this->name}' is invalid. Resource names must match the pattern ".self::RESOURCE_NAME_PATTERN
                .' (alphanumeric characters, underscores, and hyphens only).'
            );
        }
    }

    
    public static function fromArray(array $data): static
    {
        if (empty($data['uri']) || ! is_string($data['uri'])) {
            throw new ProtocolException("Invalid or missing 'uri' in ResourceDefinition data.");
        }

        if (empty($data['name']) || ! is_string($data['name'])) {
            throw new ProtocolException("Invalid or missing 'name' in ResourceDefinition data.");
        }

        return new self(
            uri: $data['uri'],
            name: $data['name'],
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            mimeType: isset($data['mimeType']) && is_string($data['mimeType']) ? $data['mimeType'] : null,
            size: isset($data['size']) && is_int($data['size']) ? $data['size'] : null,
            annotations: isset($data['annotations']) && is_array($data['annotations']) ? $data['annotations'] : []
        );
    }

    
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'name' => $this->name,
            'description' => $this->description,
            'mimeType' => $this->mimeType,
            'size' => $this->size,
            'annotations' => $this->annotations,
        ];
    }
}
