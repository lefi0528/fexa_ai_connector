<?php

declare(strict_types=1);

namespace PhpMcp\Schema;

use PhpMcp\Schema\Enum\Role;
use JsonSerializable;


class Annotations implements JsonSerializable
{
    
    public function __construct(
        public readonly ?array $audience = null,
        public readonly ?float $priority = null
    ) {
        if ($this->priority !== null && ($this->priority < 0 || $this->priority > 1)) {
            throw new \InvalidArgumentException("Annotation priority must be between 0 and 1.");
        }
        if ($this->audience !== null) {
            foreach ($this->audience as $role) {
                if (!($role instanceof Role)) {
                    throw new \InvalidArgumentException("All audience members must be instances of Role enum.");
                }
            }
        }
    }

    
    public static function make(array $audience = null, float $priority = null): static
    {
        return new static($audience, $priority);
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->audience !== null) {
            $data['audience'] = array_map(fn (Role $r) => $r->value, $this->audience);
        }
        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }
        return $data;
    }

    public static function fromArray(array $data): static
    {
        $audience = null;
        if (isset($data['audience']) && is_array($data['audience'])) {
            $audience = array_map(fn (string $r) => Role::from($r), $data['audience']);
        }
        return new static(
            $audience,
            isset($data['priority']) ? (float)$data['priority'] : null
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
