<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\JsonRpc\Response;
use PhpMcp\Schema\Tool;
use PhpMcp\Schema\JsonRpc\Result;


class ListToolsResult extends Result
{
    
    public function __construct(
        public readonly array $tools,
        public readonly ?string $nextCursor = null
    ) {}

    
    public static function make(array $tools, ?string $nextCursor = null): static
    {
        return new static($tools, $nextCursor);
    }

    public function toArray(): array
    {
        $result =  [
            'tools' => array_map(fn(Tool $t) => $t->toArray(), $this->tools),
        ];

        if ($this->nextCursor) {
            $result['nextCursor'] = $this->nextCursor;
        }

        return $result;
    }

    public static function fromArray(array $data): static
    {
        if (!isset($data['tools']) || !is_array($data['tools'])) {
            throw new \InvalidArgumentException("Missing or invalid 'tools' array in ListToolsResult data.");
        }

        return new static(
            array_map(fn(array $tool) => Tool::fromArray($tool), $data['tools']),
            $data['nextCursor'] ?? null
        );
    }

    public static function fromResponse(Response $response): static
    {
        return self::fromArray($response->result);
    }
}
