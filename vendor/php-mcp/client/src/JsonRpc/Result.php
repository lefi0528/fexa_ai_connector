<?php

declare(strict_types=1);

namespace PhpMcp\Client\JsonRpc;


abstract class Result
{
    
    
    abstract public static function fromArray(array $data): static;
}
