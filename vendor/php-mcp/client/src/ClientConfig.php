<?php

declare(strict_types=1);

namespace PhpMcp\Client;

use PhpMcp\Client\Factory\MessageIdGenerator;
use PhpMcp\Client\Model\Capabilities as ClientCapabilities;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;


class ClientConfig
{
    public LoggerInterface $logger;

    public ?CacheInterface $cache;

    public ?EventDispatcherInterface $eventDispatcher;

    public LoopInterface $loop;

    public readonly MessageIdGenerator $idGenerator;

    
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly ClientCapabilities $capabilities,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoopInterface $loop = null,
        public int $definitionCacheTtl = 3600,
        ?MessageIdGenerator $idGenerator = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->cache = $cache;
        $this->eventDispatcher = $eventDispatcher;
        $this->loop = $loop ?? Loop::get();
        $this->idGenerator = $idGenerator ?? new MessageIdGenerator;
    }
}
