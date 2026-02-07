<?php

namespace Mcp\ManualStdioExample;

use Psr\Log\LoggerInterface;

class SimpleHandlers
{
    private LoggerInterface $logger;

    private string $appVersion = '1.0-manual';

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger->info('SimpleHandlers instantiated for manual registration example.');
    }

    
    public function echoText(string $text): string
    {
        $this->logger->info("Manual tool 'echo_text' called.", ['text' => $text]);

        return 'Echo: '.$text;
    }

    
    public function getAppVersion(): string
    {
        $this->logger->info("Manual resource 'app://version' read.");

        return $this->appVersion;
    }

    
    public function greetingPrompt(string $userName): array
    {
        $this->logger->info("Manual prompt 'personalized_greeting' called.", ['userName' => $userName]);

        return [
            ['role' => 'user', 'content' => "Craft a personalized greeting for {$userName}."],
        ];
    }

    
    public function getItemDetails(string $itemId): array
    {
        $this->logger->info("Manual template 'item://{itemId}' resolved.", ['itemId' => $itemId]);

        return ['id' => $itemId, 'name' => "Item {$itemId}", 'description' => "Details for item {$itemId} from manual template."];
    }
}
