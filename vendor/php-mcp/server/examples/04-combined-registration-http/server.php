#!/usr/bin/env php
<?php



declare(strict_types=1);

chdir(__DIR__);
require_once '../../vendor/autoload.php';
require_once './DiscoveredElements.php';
require_once './ManualHandlers.php';

use Mcp\CombinedHttpExample\Manual\ManualHandlers;
use PhpMcp\Server\Defaults\BasicContainer;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\HttpServerTransport;
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
    $logger->info('Starting MCP Combined Registration (HTTP) Server...');

    $container = new BasicContainer();
    $container->set(LoggerInterface::class, $logger); 

    $server = Server::make()
        ->withServerInfo('Combined HTTP Server', '1.0.0')
        ->withLogger($logger)
        ->withContainer($container)
        ->withTool([ManualHandlers::class, 'manualGreeter'])
        ->withResource(
            [ManualHandlers::class, 'getPriorityConfigManual'],
            'config://priority',
            'priority_config_manual',
        )
        ->build();

    
    
    $server->discover(__DIR__, scanDirs: ['.']);

    $transport = new HttpServerTransport('127.0.0.1', 8081, 'mcp_combined');

    $server->listen($transport);

    $logger->info('Server listener stopped gracefully.');
    exit(0);

} catch (\Throwable $e) {
    fwrite(STDERR, "[MCP SERVER CRITICAL ERROR]\n".$e."\n");
    exit(1);
}
