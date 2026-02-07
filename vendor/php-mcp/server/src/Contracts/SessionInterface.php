<?php

declare(strict_types=1);

namespace PhpMcp\Server\Contracts;

use JsonSerializable;

interface SessionInterface extends JsonSerializable
{
    
    public function getId(): string;

    
    public function save(): void;

    
    public function get(string $key, mixed $default = null): mixed;

    
    public function set(string $key, mixed $value, bool $overwrite = true): void;

    
    public function has(string $key): bool;

    
    public function forget(string $key): void;

    
    public function clear(): void;

    
    public function pull(string $key, mixed $default = null): mixed;

    
    public function all(): array;

    
    public function hydrate(array $attributes): void;

    
    public function queueMessage(string $message): void;

    
    public function dequeueMessages(): array;

    
    public function hasQueuedMessages(): bool;

    
    public function getHandler(): SessionHandlerInterface;
}
