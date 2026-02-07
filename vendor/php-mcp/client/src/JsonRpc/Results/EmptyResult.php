<?php

namespace PhpMcp\Client\JsonRpc\Results;

use PhpMcp\Client\JsonRpc\Result;


class EmptyResult extends Result
{
    
    public function __construct()
    {
    }

    public static function fromArray(array $data): static
    {
        return new static();
    }

    
    public function toArray(): array
    {
        return []; 
    }
}
