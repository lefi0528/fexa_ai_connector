<?php

declare(strict_types=1);

namespace PhpMcp\Schema;

use JsonSerializable;


class ResourceReference implements JsonSerializable
{
    public string $type = 'ref/resource';

    
    public function __construct(
        public readonly string $uri,
    ) {}

    public static function make(string $uri): static
    {
        return new static($uri);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'uri' => $this->uri,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
