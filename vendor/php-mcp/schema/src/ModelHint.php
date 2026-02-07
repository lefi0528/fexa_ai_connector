<?php

declare(strict_types=1);

namespace PhpMcp\Schema;

use JsonSerializable;


class ModelHint implements JsonSerializable
{
    
    public function __construct(
        public readonly ?string $name = null,
    ) {
    }

    
    public static function make(?string $name = null): static
    {
        return new static($name);
    }

    public function toArray(): array
    {
        if ($this->name === null) {
            return [];
        }

        return ['name' => $this->name];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
