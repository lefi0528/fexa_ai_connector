<?php

namespace Mcp\CombinedHttpExample\Manual;

use Psr\Log\LoggerInterface;

class ManualHandlers
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    
    public function manualGreeter(string $user): string
    {
        $this->logger->info("Manual tool 'manual_greeter' called for {$user}");

        return "Hello {$user}, from manual registration!";
    }

    
    public function getPriorityConfigManual(): string
    {
        $this->logger->info("Manual resource 'config://priority' read.");

        return 'Manual Priority Config: HIGH (overrides discovered)';
    }
}
