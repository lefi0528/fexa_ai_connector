<?php

namespace PhpMcp\Server\Attributes;

use Attribute;
use PhpMcp\Schema\ToolAnnotations;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class McpTool
{
    
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?ToolAnnotations $annotations = null,
    ) {
    }
}
