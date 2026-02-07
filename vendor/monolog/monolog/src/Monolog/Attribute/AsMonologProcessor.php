<?php declare(strict_types=1);



namespace Monolog\Attribute;


#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsMonologProcessor
{
    
    public function __construct(
        public readonly ?string $channel = null,
        public readonly ?string $handler = null,
        public readonly ?string $method = null,
        public readonly ?int $priority = null
    ) {
    }
}
