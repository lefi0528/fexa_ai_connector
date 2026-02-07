<?php

declare(strict_types=1);

namespace PhpMcp\Schema\JsonRpc;

use JsonSerializable;
use PhpMcp\Schema\Constants;


abstract class Message implements JsonSerializable
{
    public function __construct(
        public readonly string $jsonrpc = Constants::JSONRPC_VERSION
    ) {}

    public abstract function getId(): string|int|null;

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    abstract public function toArray(): array;
}
