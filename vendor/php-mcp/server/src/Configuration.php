<?php

declare(strict_types=1);

namespace PhpMcp\Server;

use PhpMcp\Schema\Implementation;
use PhpMcp\Schema\ServerCapabilities;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use React\EventLoop\LoopInterface;


class Configuration
{
    
    public function __construct(
        public readonly Implementation $serverInfo,
        public readonly ServerCapabilities $capabilities,
        public readonly LoggerInterface $logger,
        public readonly LoopInterface $loop,
        public readonly ?CacheInterface $cache,
        public readonly ContainerInterface $container,
        public readonly int $paginationLimit = 50,
        public readonly ?string $instructions = null,
    ) {}
}
