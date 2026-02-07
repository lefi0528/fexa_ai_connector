<?php

namespace PhpMcp\Server\Attributes;

use Attribute;
use PhpMcp\Schema\Annotations;


#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class McpResourceTemplate
{
    
    public function __construct(
        public string $uriTemplate,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?Annotations $annotations = null,
    ) {
    }
}
