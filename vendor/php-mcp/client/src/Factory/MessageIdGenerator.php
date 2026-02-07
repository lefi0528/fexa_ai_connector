<?php

declare(strict_types=1);

namespace PhpMcp\Client\Factory;


class MessageIdGenerator
{
    private int $counter = 0;

    private string $prefix;

    public function __construct(string $prefix = 'mcp-req-')
    {
        
        $this->prefix = $prefix.getmypid().'-'.bin2hex(random_bytes(4)).'-';
    }

    public function generate(): string
    {
        return $this->prefix.(++$this->counter);
    }
}
