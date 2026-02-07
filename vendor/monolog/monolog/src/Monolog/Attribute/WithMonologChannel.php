<?php declare(strict_types=1);



namespace Monolog\Attribute;


#[\Attribute(\Attribute::TARGET_CLASS)]
final class WithMonologChannel
{
    public function __construct(
        public readonly string $channel
    ) {
    }
}
