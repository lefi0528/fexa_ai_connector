#!/usr/bin/env php
<?php



declare(strict_types=1);

chdir(__DIR__);
require_once '../../vendor/autoload.php';
require_once 'McpElements.php';
require_once 'UserIdCompletionProvider.php';

use PhpMcp\Schema\ServerCapabilities;
use PhpMcp\Server\Defaults\BasicContainer;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\HttpServerTransport;
use PhpMcp\Server\Transports\StreamableHttpServerTransport;
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
    $logger->info('Starting MCP HTTP User Profile Server...');

    
    $container = new BasicContainer();
    $container->set(LoggerInterface::class, $logger);

    $server = Server::make()
        ->withServerInfo('HTTP User Profiles', '1.0.0')
        ->withCapabilities(ServerCapabilities::make(completions: true, logging: true))
        ->withLogger($logger)
        ->withContainer($container)
        ->withTool(
            function (float $a, float $b, string $operation = 'add'): array {
                $result = match ($operation) {
                    'add' => $a + $b,
                    'subtract' => $a - $b,
                    'multiply' => $a * $b,
                    'divide' => $b != 0 ? $a / $b : throw new \InvalidArgumentException('Cannot divide by zero'),
                    default => throw new \InvalidArgumentException("Unknown operation: {$operation}")
                };

                return [
                    'operation' => $operation,
                    'operands' => [$a, $b],
                    'result' => $result
                ];
            },
            name: 'calculator',
            description: 'Perform basic math operations (add, subtract, multiply, divide)'
        )
        ->withResource(
            function (): array {
                $memoryUsage = memory_get_usage(true);
                $memoryPeak = memory_get_peak_usage(true);
                $uptime = time() - $_SERVER['REQUEST_TIME_FLOAT'] ?? time();
                $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'CLI';

                return [
                    'server_time' => date('Y-m-d H:i:s'),
                    'uptime_seconds' => $uptime,
                    'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                    'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
                    'php_version' => PHP_VERSION,
                    'server_software' => $serverSoftware,
                    'operating_system' => PHP_OS_FAMILY,
                    'status' => 'healthy'
                ];
            },
            uri: 'system://status',
            name: 'system_status',
            description: 'Current system status and runtime information',
            mimeType: 'application/json'
        )
        ->build();

    $server->discover(__DIR__, ['.']);

    
    $transport = new StreamableHttpServerTransport('127.0.0.1', 8080, 'mcp');

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
