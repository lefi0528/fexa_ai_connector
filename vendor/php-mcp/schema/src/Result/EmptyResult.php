<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\JsonRpc\Result;


class EmptyResult extends Result
{
    
    public function __construct() {}

    
    public function toArray(): array
    {
        return []; 
    }

    public static function make(): static
    {
        return new static();
    }

    public static function fromArray(array $data): static
    {
        return new static();
    }

    public function jsonSerialize(): mixed
    {
        return new \stdClass();
    }
}
