<?php

namespace PhpMcp\Server\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class McpPrompt
{
    
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
    ) {
    }
}
