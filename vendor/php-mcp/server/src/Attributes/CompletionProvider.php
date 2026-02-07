<?php

declare(strict_types=1);

namespace PhpMcp\Server\Attributes;

use Attribute;
use PhpMcp\Server\Contracts\CompletionProviderInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CompletionProvider
{
    
    public function __construct(
        public ?string $providerClass = null,
        public string|CompletionProviderInterface|null $provider = null,
        public ?array $values = null,
        public ?string $enum = null,
    ) {
        if (count(array_filter([$provider, $values, $enum])) !== 1) {
            throw new \InvalidArgumentException('Only one of provider, values, or enum can be set');
        }
    }
}
