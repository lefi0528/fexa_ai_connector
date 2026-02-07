<?php

namespace Mcp\HttpUserProfileExample;

use PhpMcp\Server\Attributes\CompletionProvider;
use PhpMcp\Server\Attributes\McpPrompt;
use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpResourceTemplate;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Exception\McpServerException;
use Psr\Log\LoggerInterface;

class McpElements
{
    
    private array $users = [
        '101' => ['name' => 'Alice', 'email' => 'alice@example.com', 'role' => 'admin'],
        '102' => ['name' => 'Bob', 'email' => 'bob@example.com', 'role' => 'user'],
        '103' => ['name' => 'Charlie', 'email' => 'charlie@example.com', 'role' => 'user'],
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger->debug('HttpUserProfileExample McpElements instantiated.');
    }

    
    #[McpResourceTemplate(
        uriTemplate: 'user://{userId}/profile',
        name: 'user_profile',
        description: 'Get profile information for a specific user ID.',
        mimeType: 'application/json'
    )]

    public function getUserProfile(
        #[CompletionProvider(values: ['101', '102', '103'])]
        string $userId
    ): array {
        $this->logger->info('Reading resource: user profile', ['userId' => $userId]);
        if (! isset($this->users[$userId])) {
            
            throw McpServerException::invalidParams("User profile not found for ID: {$userId}");
        }

        return $this->users[$userId];
    }

    
    #[McpResource(
        uri: 'user://list/ids',
        name: 'user_id_list',
        description: 'Provides a list of all available user IDs.',
        mimeType: 'application/json'
    )]
    public function listUserIds(): array
    {
        $this->logger->info('Reading resource: user ID list');

        return array_keys($this->users);
    }

    
    #[McpTool(name: 'send_welcome')]
    public function sendWelcomeMessage(string $userId, ?string $customMessage = null): array
    {
        $this->logger->info('Executing tool: send_welcome', ['userId' => $userId]);
        if (! isset($this->users[$userId])) {
            return ['success' => false, 'error' => "User ID {$userId} not found."];
        }
        $user = $this->users[$userId];
        $message = "Welcome, {$user['name']}!";
        if ($customMessage) {
            $message .= ' ' . $customMessage;
        }
        
        $this->logger->info("Simulated sending message to {$user['email']}: {$message}");

        return ['success' => true, 'message_sent' => $message];
    }

    #[McpTool(name: 'test_tool_without_params')]
    public function testToolWithoutParams()
    {
        return ['success' => true, 'message' => 'Test tool without params'];
    }

    
    #[McpPrompt(name: 'generate_bio_prompt')]
    public function generateBio(
        #[CompletionProvider(provider: UserIdCompletionProvider::class)]
        string $userId,
        string $tone = 'professional'
    ): array {
        $this->logger->info('Executing prompt: generate_bio', ['userId' => $userId, 'tone' => $tone]);
        if (! isset($this->users[$userId])) {
            throw McpServerException::invalidParams("User not found for bio prompt: {$userId}");
        }
        $user = $this->users[$userId];

        return [
            ['role' => 'user', 'content' => "Write a short, {$tone} biography for {$user['name']} (Role: {$user['role']}, Email: {$user['email']}). Highlight their role within the system."],
        ];
    }
}
