<?php

declare(strict_types=1);

namespace PhpMcp\Schema\JsonRpc;

use PhpMcp\Schema\Constants;

class Response extends Message
{
    
    public function __construct(
        string $jsonrpc,
        public readonly string|int $id,
        public readonly Result|array $result,
    ) {
        parent::__construct($jsonrpc);
    }

    public function getId(): string|int|null
    {
        return $this->id;
    }

    
    public static function make(string|int $id, Result|array $result): static
    {
        return new static(Constants::JSONRPC_VERSION, $id, $result);
    }

    public function toArray(): array
    {
        return [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id,
            'result' => is_array($this->result) ? $this->result : $this->result->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id,
            'result' => is_array($this->result) ? $this->result : $this->result->jsonSerialize(),
        ];
    }

    public static function fromArray(array $data): static
    {
        if (($data['jsonrpc'] ?? null) !== Constants::JSONRPC_VERSION) {
            throw new \InvalidArgumentException('Invalid or missing "jsonrpc" version for Response.');
        }
        if (!isset($data['id'])) {
            throw new \InvalidArgumentException('Missing "id" for Response.');
        }
        if (!is_string($data['id']) && !is_int($data['id'])) {
            throw new \InvalidArgumentException('Invalid "id" type for Response.');
        }
        if (!isset($data['result'])) {
            throw new \InvalidArgumentException('Response must contain "result" field.');
        }
        return new static(
            $data['jsonrpc'],
            $data['id'],
            $data['result']
        );
    }
}
