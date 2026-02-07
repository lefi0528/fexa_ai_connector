<?php

namespace PhpMcp\Client\JsonRpc\Results;

use PhpMcp\Client\JsonRpc\Result;
use PhpMcp\Client\Model\Definitions\PromptDefinition;

class ListPromptsResult extends Result
{
    
    public function __construct(
        public readonly array $prompts,
        public readonly ?string $nextCursor = null
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            prompts: array_map(fn (array $promptData) => PromptDefinition::fromArray($promptData), $data['prompts']),
            nextCursor: $data['nextCursor'] ?? null
        );
    }

    public function toArray(): array
    {
        $result = [
            'prompts' => array_map(fn (PromptDefinition $p) => $p->toArray(), $this->prompts),
        ];

        if ($this->nextCursor) {
            $result['nextCursor'] = $this->nextCursor;
        }

        return $result;
    }
}
