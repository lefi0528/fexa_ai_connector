<?php

declare(strict_types=1);

namespace PhpMcp\Schema;

use JsonSerializable;


class ToolAnnotations implements JsonSerializable
{
    
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?bool $readOnlyHint = null,
        public readonly ?bool $destructiveHint = null,
        public readonly ?bool $idempotentHint = null,
        public readonly ?bool $openWorldHint = null
    ) {
    }

    
    public static function make(?string $title = null, ?bool $readOnlyHint = null, ?bool $destructiveHint = null, ?bool $idempotentHint = null, ?bool $openWorldHint = null): static
    {
        return new static($title, $readOnlyHint, $destructiveHint, $idempotentHint, $openWorldHint);
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->title !== null) {
            $data['title'] = $this->title;
        }
        if ($this->readOnlyHint !== null) {
            $data['readOnlyHint'] = $this->readOnlyHint;
        }
        if ($this->destructiveHint !== null) {
            $data['destructiveHint'] = $this->destructiveHint;
        }
        if ($this->idempotentHint !== null) {
            $data['idempotentHint'] = $this->idempotentHint;
        }
        if ($this->openWorldHint !== null) {
            $data['openWorldHint'] = $this->openWorldHint;
        }
        return $data;
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['title'] ?? null,
            $data['readOnlyHint'] ?? null,
            $data['destructiveHint'] ?? null,
            $data['idempotentHint'] ?? null,
            $data['openWorldHint'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
