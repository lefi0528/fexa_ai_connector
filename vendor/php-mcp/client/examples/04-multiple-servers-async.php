<?php

declare(strict_types=1);





require __DIR__.'/../vendor/autoload.php';

use PhpMcp\Client\Client;
use PhpMcp\Client\Enum\ConnectionStatus;
use PhpMcp\Client\Enum\TransportType;
use PhpMcp\Client\Model\Capabilities as ClientCapabilities;
use PhpMcp\Client\Model\Content\EmbeddedResource;
use PhpMcp\Client\Model\Content\TextContent;
use PhpMcp\Client\ServerConfig;
use PhpMcp\Client\StreamLogger;
use React\EventLoop\Loop;

use function React\Promise\all;

$clientName = 'MultiServerAsyncClient';
$clientVersion = '0.3.0';
$clientCapabilities = ClientCapabilities::forClient(supportsSampling: false);
$logger = new StreamLogger(__DIR__.'/client_multi_async.log');
$loop = Loop::get();


$stdioServerConfig = new ServerConfig(
    name: 'local_stdio_server',
    transport: TransportType::Stdio,
    timeout: 10,
    command: 'php',
    args: [__DIR__.'/../../server/samples/php_stdio/server.php']
);

$httpServerConfig = new ServerConfig(
    name: 'http_sample_server',
    transport: TransportType::Http,
    timeout: 30,
    url: 'http://127.0.0.1:8080/mcp/sse'
);


$stdioClient = Client::make()
    ->withClientInfo($clientName, $clientVersion)
    ->withCapabilities($clientCapabilities)
    ->withLogger($logger)
    ->withLoop($loop)
    ->withServerConfig($stdioServerConfig)
    ->build();

$httpClient = Client::make()
    ->withClientInfo($clientName, $clientVersion)
    ->withCapabilities($clientCapabilities)
    ->withLogger($logger)
    ->withLoop($loop)
    ->withServerConfig($httpServerConfig)
    ->build();

echo "MCP Clients created. Attempting asynchronous initialization...\n";
echo "(Ensure servers are running)\n";

$stdioResult = null;
$httpResult = null;


$initPromises = [
    'stdio' => $stdioClient->initializeAsync(),
    'http' => $httpClient->initializeAsync(),
];

all($initPromises)->then(
    function (array $clients) use ($stdioClient, $httpClient, $logger) {
        echo "\n--- All connections initialized successfully! ---\n";
        $logger->info('All clients initialized.');

        

        
        echo "\n[2] Listing tools from both servers...\n";

        return all([
            'stdio_list' => $stdioClient->listToolsAsync(),
            'http_list' => $httpClient->listToolsAsync(),
        ]);
    },
    function (Throwable $error) {
        
        echo "\n[ERROR] Failed to initialize one or more clients: ".$error->getMessage()."\n";
        Loop::stop();
        throw $error;
    }
)->then(
    
    function (array $listResults) use ($stdioClient, $httpClient, $logger) {
        $stdioTools = $listResults['stdio_list'];
        $httpTools = $listResults['http_list'];
        echo "   Tools from [{$stdioClient->getServerName()}]: ".count($stdioTools)."\n";
        echo "   Tools from [{$httpClient->getServerName()}]: ".count($httpTools)."\n";
        $logger->info('Tool lists retrieved', ['stdio_count' => count($stdioTools), 'http_count' => count($httpTools)]);

        
        echo "\n[3] Calling tools concurrently ('greeter' and 'greet_user')...\n";

        return all([
            'stdio_greet' => $stdioClient->callToolAsync('greeter', ['name' => 'Async Stdio']), 
            'http_greet' => $httpClient->callToolAsync('greet_user', ['name' => 'Async HTTP']), 
        ]);
    }
)->then(
    
    function (array $callResults) use ($stdioClient, $httpClient, $logger) {
        $logger->info('Tool calls completed.');
        
        
        $stdioResult = $callResults['stdio_greet'];
        if ($stdioResult->isSuccess() && isset($stdioResult->content[0]) && $stdioResult->content[0] instanceof TextContent) {
            echo "   Result from [{$stdioClient->getServerName()}] greeter: ".$stdioResult->content[0]->text."\n";
        } else {
            echo "   Error/unexpected result from [{$stdioClient->getServerName()}] greeter.\n";
        }

        
        
        $httpResult = $callResults['http_greet'];
        if ($httpResult->isSuccess() && isset($httpResult->content[0]) && $httpResult->content[0] instanceof TextContent) {
            echo "   Result from [{$httpClient->getServerName()}] greet_user: ".$httpResult->content[0]->text."\n";
        } else {
            echo "   Error/unexpected result from [{$httpClient->getServerName()}] greet_user.\n";
        }

        
        echo "\n[4] Reading resources concurrently ('user://data' and 'config://app/name')...\n";

        return all([
            'stdio_read' => $stdioClient->readResourceAsync('user://data'),
            'http_read' => $httpClient->readResourceAsync('config://app/name'),
        ]);
    }
)->then(
    
    function (array $resourceResults) use ($stdioClient, $httpClient, $logger) {
        $logger->info('Resource reads completed.');
        
        
        $stdioRead = $resourceResults['stdio_read'];
        $stdioContent = $stdioRead->contents[0] ?? null;
        if ($stdioContent instanceof EmbeddedResource) {
            echo "   Read [{$stdioClient->getServerName()}] user://data: ".($stdioContent->text ?? '[OK/Binary]')."\n";
        } else {
            echo "   Read [{$stdioClient->getServerName()}] user://data failed or empty.\n";
        }

        
        
        $httpRead = $resourceResults['http_read'];
        $httpContent = $httpRead->contents[0] ?? null;
        if ($httpContent instanceof EmbeddedResource) {
            echo "   Read [{$httpClient->getServerName()}] config://app/name: ".($httpContent->text ?? '[OK/Binary]')."\n";
        } else {
            echo "   Read [{$httpClient->getServerName()}] config://app/name failed or empty.\n";
        }

        
        
    }
)->catch(
    
    function (Throwable $error) use ($loop) {
        echo "\n[ERROR] Async operation failed: ".get_class($error).' - '.$error->getMessage()."\n";
        $loop->stop();
    }
)->finally(
    
    function () use ($stdioClient, $httpClient, $logger, $loop) {
        echo "\nDisconnecting clients (async)...\n";
        $disconnectPromises = [];

        if ($stdioClient->getStatus() !== ConnectionStatus::Closed && $stdioClient->getStatus() !== ConnectionStatus::Disconnected) {
            $disconnectPromises['stdio'] = $stdioClient->disconnectAsync();
        }

        if ($httpClient->getStatus() !== ConnectionStatus::Closed && $httpClient->getStatus() !== ConnectionStatus::Disconnected) {
            $disconnectPromises['http'] = $httpClient->disconnectAsync();
        }

        if (empty($disconnectPromises)) {
            echo "Clients already disconnected.\n";

            return resolve(null);
        }

        return all($disconnectPromises)->then(
            function () use ($logger) {
                echo "All clients disconnected successfully.\n";
                $logger->info('Disconnect all finished.');
            },
            function (Throwable $e) use ($logger) {
                echo "[Warning] Error during disconnect: {$e->getMessage()}\n";
                $logger->warning('Error during disconnectAll', ['exception' => $e]);
            }
        )->finally(function () use ($loop) {
            $loop->stop();
        });
    }
);


echo "\nStarting Event Loop...\n";
$loop->run();

echo "\nAsync Example finished.\n";
