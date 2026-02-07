<?php

namespace PhpMcp\Client\JsonRpc\Results;

use PhpMcp\Client\JsonRpc\Result;
use PhpMcp\Client\Model\Content\PromptMessage;

class GetPromptResult extends Result
{
    
    public function __construct(
        public readonly array $messages,
        public readonly ?string $description = null
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            messages: array_map(fn (array $messageData) => PromptMessage::fromArray($messageData), $data['messages']),
            description: $data['description'] ?? null
        );
    }

    
    public function toArray(): array
    {
        $result = [
            'messages' => array_map(fn ($message) => $message->toArray(), $this->messages),
        ];

        if ($this->description !== null) {
            $result['description'] = $this->description;
        }

        return $result;
    }
}
