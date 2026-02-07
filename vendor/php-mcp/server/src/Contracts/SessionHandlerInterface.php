<?php

declare(strict_types=1);

namespace PhpMcp\Server\Contracts;

interface SessionHandlerInterface
{
    
    public function read(string $id): string|false;

    
    public function write(string $id, string $data): bool;

    
    public function destroy(string $id): bool;

    
    public function gc(int $maxLifetime): array;
}
