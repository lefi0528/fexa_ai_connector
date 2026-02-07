#!/usr/bin/env php
<?php



declare(strict_types=1);

chdir(__DIR__);
require_once '../../vendor/autoload.php';
require_once './Services.php';
require_once './McpTaskHandlers.php';

use Mcp\DependenciesStdioExample\Services;
use PhpMcp\Server\Defaults\BasicContainer;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class StderrLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        fwrite(STDERR, sprintf("[%s][%s] %s %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message, empty($context) ? '' : json_encode($context)));
    }
}

try {
    $logger = new StderrLogger();
    $logger->info('Starting MCP Custom Dependencies (Stdio) Server...');

    $container = new BasicContainer();
    $container->set(LoggerInterface::class, $logger);

    $taskRepo = new Services\InMemoryTaskRepository($logger);
    $container->set(Services\TaskRepositoryInterface::class, $taskRepo);

    $statsService = new Services\SystemStatsService($taskRepo);
    $container->set(Services\StatsServiceInterface::class, $statsService);

    $server = Server::make()
        ->withServerInfo('Task Manager Server', '1.0.0')
        ->withLogger($logger)
        ->withContainer($container)
        ->build();

    $server->discover(__DIR__, ['.']);

    $transport = new StdioServerTransport();
    $server->listen($transport);

    $logger->info('Server listener stopped gracefully.');
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "[MCP SERVER CRITICAL ERROR]\n");
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    fwrite(STDERR, 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}
