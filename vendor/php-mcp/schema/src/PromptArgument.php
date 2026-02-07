<?php

declare(strict_types=1);

namespace PhpMcp\Schema;

use JsonSerializable;


class PromptArgument implements JsonSerializable
{
    
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?bool $required = null
    ) {
    }

    
    public static function make(string $name, ?string $description = null, ?bool $required = null): static
    {
        return new static($name, $description, $required);
    }

    public function toArray(): array
    {
        $data = ['name' => $this->name];
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->required !== null) {
            $data['required'] = $this->required;
        } 
        return $data;
    }

    public static function fromArray(array $data): static
    {
        if (empty($data['name']) || !is_string($data['name'])) {
            throw new \InvalidArgumentException("Invalid or missing 'name' in PromptArgument data.");
        }
        return new static(
            name: $data['name'],
            description: $data['description'] ?? null,
            required: $data['required'] ?? null 
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
