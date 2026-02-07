<?php

namespace PhpMcp\Client\Model\Definitions;

use PhpMcp\Client\Exception\ProtocolException;


class ResourceTemplateDefinition
{
    
    private const RESOURCE_NAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';

    
    private const URI_TEMPLATE_PATTERN = '/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\/.*{[^{}]+}.*/';

    
    public function __construct(
        public readonly string $uriTemplate,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $mimeType,
        public readonly array $annotations = []
    ) {
        $this->validate();
    }

    
    private function validate(): void
    {
        if (! preg_match(self::URI_TEMPLATE_PATTERN, $this->uriTemplate)) {
            throw new \InvalidArgumentException(
                "Resource URI template '{$this->uriTemplate}' is invalid. URI templates must match the pattern "
                .self::URI_TEMPLATE_PATTERN.' (valid scheme followed by :// and path with placeholder(s) in curly braces).'
            );
        }

        if (! preg_match(self::RESOURCE_NAME_PATTERN, $this->name)) {
            throw new \InvalidArgumentException(
                "Resource name '{$this->name}' is invalid. Resource names must match the pattern ".self::RESOURCE_NAME_PATTERN
                .' (alphanumeric characters, underscores, and hyphens only).'
            );
        }
    }

    
    public static function fromArray(array $data): static
    {
        if (empty($data['uriTemplate']) || ! is_string($data['uriTemplate'])) {
            throw new ProtocolException("Invalid or missing 'uriTemplate' in ResourceTemplateDefinition data.");
        }
        if (empty($data['name']) || ! is_string($data['name'])) {
            throw new ProtocolException("Invalid or missing 'name' in ResourceTemplateDefinition data.");
        }

        return new self(
            uriTemplate: $data['uriTemplate'],
            name: $data['name'],
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            mimeType: isset($data['mimeType']) && is_string($data['mimeType']) ? $data['mimeType'] : null,
            annotations: isset($data['annotations']) && is_array($data['annotations']) ? $data['annotations'] : []
        );
    }

    
    public function toArray(): array
    {
        return [
            'uriTemplate' => $this->uriTemplate,
            'name' => $this->name,
            'description' => $this->description,
            'mimeType' => $this->mimeType,
            'annotations' => $this->annotations,
        ];
    }
}
