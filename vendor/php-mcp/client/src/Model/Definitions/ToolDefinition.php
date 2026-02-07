<?php

namespace PhpMcp\Client\Model\Definitions;

use PhpMcp\Client\Exception\ProtocolException;


class ToolDefinition
{
    
    private const TOOL_NAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';

    
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $inputSchema,
    ) {
        $this->validate();
    }

    
    private function validate(): void
    {
        if (! preg_match(self::TOOL_NAME_PATTERN, $this->name)) {
            throw new \InvalidArgumentException(
                "Tool name '{$this->name}' is invalid. Tool names must match the pattern ".self::TOOL_NAME_PATTERN
                .' (alphanumeric characters, underscores, and hyphens only).'
            );
        }
    }

    
    public static function fromArray(array $data): static
    {
        if (empty($data['name']) || ! is_string($data['name'])) {
            throw new ProtocolException("Invalid or missing 'name' in ToolDefinition data.");
        }

        
        $inputSchema = $data['inputSchema'] ?? ['type' => 'object'];
        if (! is_array($inputSchema)) {
            throw new ProtocolException("Invalid 'inputSchema' in ToolDefinition data, must be an array/object.");
        }

        return new self(
            name: $data['name'],
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            inputSchema: $inputSchema
        );
    }

    
    public function toArray(): array
    {

        return [
            'name' => $this->name,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema,
        ];
    }
}
