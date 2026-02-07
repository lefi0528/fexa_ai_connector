<?php

namespace Mcp\DependenciesStdioExample;

use Mcp\DependenciesStdioExample\Services\StatsServiceInterface;
use Mcp\DependenciesStdioExample\Services\TaskRepositoryInterface;
use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpTool;
use Psr\Log\LoggerInterface;

class McpTaskHandlers
{
    private TaskRepositoryInterface $taskRepo;

    private StatsServiceInterface $statsService;

    private LoggerInterface $logger;

    
    public function __construct(
        TaskRepositoryInterface $taskRepo,
        StatsServiceInterface $statsService,
        LoggerInterface $logger
    ) {
        $this->taskRepo = $taskRepo;
        $this->statsService = $statsService;
        $this->logger = $logger;
        $this->logger->info('McpTaskHandlers instantiated with dependencies.');
    }

    
    #[McpTool(name: 'add_task')]
    public function addTask(string $userId, string $description): array
    {
        $this->logger->info("Tool 'add_task' invoked", ['userId' => $userId]);

        return $this->taskRepo->addTask($userId, $description);
    }

    
    #[McpTool(name: 'list_user_tasks')]
    public function listUserTasks(string $userId): array
    {
        $this->logger->info("Tool 'list_user_tasks' invoked", ['userId' => $userId]);

        return $this->taskRepo->getTasksForUser($userId);
    }

    
    #[McpTool(name: 'complete_task')]
    public function completeTask(int $taskId): array
    {
        $this->logger->info("Tool 'complete_task' invoked", ['taskId' => $taskId]);
        $success = $this->taskRepo->completeTask($taskId);

        return ['success' => $success, 'message' => $success ? "Task {$taskId} completed." : "Task {$taskId} not found."];
    }

    
    #[McpResource(uri: 'stats://system/overview', name: 'system_stats', mimeType: 'application/json')]
    public function getSystemStatistics(): array
    {
        $this->logger->info("Resource 'stats://system/overview' invoked");

        return $this->statsService->getSystemStats();
    }
}
