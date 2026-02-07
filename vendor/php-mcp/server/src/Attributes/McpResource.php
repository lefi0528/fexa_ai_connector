<?php

namespace PhpMcp\Server\Attributes;

use Attribute;
use PhpMcp\Schema\Annotations;


#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class McpResource
{
    
    public function __construct(
        public string $uri,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?Annotations $annotations = null,
    ) {
    }
}
