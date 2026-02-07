<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\JsonRpc\Result;


class CompletionCompleteResult extends Result
{
    
    public function __construct(
        public readonly array $values,
        public readonly ?int $total = null,
        public readonly ?bool $hasMore = null
    ) {
        if (count($this->values) > 100) {
            throw new \InvalidArgumentException('Values must not exceed 100 items');
        }
    }

    public function toArray(): array
    {
        $completion = [
            'values' => $this->values,
        ];

        if ($this->total !== null) {
            $completion['total'] = $this->total;
        }
        if ($this->hasMore !== null) {
            $completion['hasMore'] = $this->hasMore;
        }

        return ['completion' => $completion];
    }

    
    public static function make(array $values, ?int $total = null, ?bool $hasMore = null): static
    {
        return new static($values, $total, $hasMore);
    }
}
