#!/usr/bin/env php
<?php

declare(strict_types=1);

chdir(__DIR__);
require_once '../../vendor/autoload.php';
require_once './EnvToolHandler.php';

use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;
use Psr\Log\AbstractLogger;



class StderrLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        fwrite(STDERR, sprintf("[%s][%s] %s %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message, empty($context) ? '' : json_encode($context)));
    }
}

try {
    $logger = new StderrLogger();
    $logger->info('Starting MCP Stdio Environment Variable Example Server...');

    $server = Server::make()
        ->withServerInfo('Env Var Server', '1.0.0')
        ->withLogger($logger)
        ->build();

    $server->discover(__DIR__, ['.']);

    $transport = new StdioServerTransport();
    $server->listen($transport);

    $logger->info('Server listener stopped gracefully.');
    exit(0);

} catch (\Throwable $e) {
    fwrite(STDERR, "[MCP SERVER CRITICAL ERROR]\n".$e."\n");
    exit(1);
}
