<?php

declare(strict_types=1);

namespace PhpMcp\Schema\JsonRpc;

use JsonSerializable;


abstract class Result implements JsonSerializable
{
    
    abstract public function toArray(): array;

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
