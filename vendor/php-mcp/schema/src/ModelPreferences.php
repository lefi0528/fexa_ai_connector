<?php

declare(strict_types=1);

namespace PhpMcp\Schema;

use JsonSerializable;


class ModelPreferences implements JsonSerializable
{
    
    public function __construct(
        public readonly ?array $hints = null,
        public readonly ?float $costPriority = null,
        public readonly ?float $speedPriority = null,
        public readonly ?float $intelligencePriority = null,
    ) {
    }

    
    public static function make(?array $hints = null, ?float $costPriority = null, ?float $speedPriority = null, ?float $intelligencePriority = null): static
    {
        return new static($hints, $costPriority, $speedPriority, $intelligencePriority);
    }

    public function toArray(): array
    {
        $result = [];
        if ($this->hints !== null) {
            $result['hints'] = array_map(fn ($hint) => $hint->toArray(), $this->hints);
        }
        if ($this->costPriority !== null) {
            $result['costPriority'] = $this->costPriority;
        }
        if ($this->speedPriority !== null) {
            $result['speedPriority'] = $this->speedPriority;
        }
        if ($this->intelligencePriority !== null) {
            $result['intelligencePriority'] = $this->intelligencePriority;
        }
        return $result;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
