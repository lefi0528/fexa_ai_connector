<?php

namespace PhpMcp\Client\Model\Definitions;


class PromptArgumentDefinition
{
    
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $required = false
    ) {}

    
    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            required: $data['required'] ?? false
        );
    }

    
    public function toArray(): array
    {
        $array = [
            'name' => $this->name,
            'required' => $this->required,
        ];

        if ($this->description) {
            $array['description'] = $this->description;
        }

        return $array;
    }
}
