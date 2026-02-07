<?php

namespace PhpMcp\Client\Model\Definitions;

use PhpMcp\Client\Exception\ProtocolException;


class PromptDefinition
{
    
    private const PROMPT_NAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';

    
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $arguments = []
    ) {
        $this->validate();
    }

    
    private function validate(): void
    {
        if (! preg_match(self::PROMPT_NAME_PATTERN, $this->name)) {
            throw new \InvalidArgumentException(
                "Prompt name '{$this->name}' is invalid. Prompt names must match the pattern ".self::PROMPT_NAME_PATTERN
                .' (alphanumeric characters, underscores, and hyphens only).'
            );
        }
    }

    public function isTemplate(): bool
    {
        return ! empty($this->arguments);
    }

    
    public static function fromArray(array $data): static
    {

        if (empty($data['name']) || ! is_string($data['name'])) {
            throw new ProtocolException("Invalid or missing 'name' in PromptDefinition data.");
        }

        $args = [];
        if (isset($data['arguments'])) {
            if (! is_array($data['arguments'])) {
                throw new ProtocolException("Invalid 'arguments' format in PromptDefinition.");
            }
            foreach ($data['arguments'] as $argData) {
                if (! is_array($argData)) {
                    throw new ProtocolException('Invalid argument item format in PromptDefinition.');
                }
                $args[] = PromptArgumentDefinition::fromArray($argData);
            }
        }

        return new self(
            name: $data['name'],
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            arguments: $args
        );
    }

    
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'arguments' => array_map(fn ($arg) => $arg->toArray(), $this->arguments),
        ];
    }
}
