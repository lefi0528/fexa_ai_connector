<?php

declare(strict_types=1);

namespace PhpMcp\Server\Contracts;

interface CompletionProviderInterface
{
    
    public function getCompletions(string $currentValue, SessionInterface $session): array;
}
