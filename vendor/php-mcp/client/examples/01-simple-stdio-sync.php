<?php

declare(strict_types=1);




require __DIR__.'/../vendor/autoload.php';

use PhpMcp\Client\Client;
use PhpMcp\Client\Enum\TransportType;
use PhpMcp\Client\Exception\McpClientException;
use PhpMcp\Client\Exception\RequestException;
use PhpMcp\Client\Model\Capabilities as ClientCapabilities;
use PhpMcp\Client\Model\Content\EmbeddedResource;
use PhpMcp\Client\Model\Content\TextContent;
use PhpMcp\Client\ServerConfig;
use PhpMcp\Client\StreamLogger;


$clientName = 'MySimplePHPClient';
$clientVersion = '0.1.0';
$clientCapabilities = ClientCapabilities::forClient(supportsSampling: false);
$logger = new StreamLogger(__DIR__.'/client_stdio.log');


$stdioServerConfig = new ServerConfig(
    name: 'local_stdio_server',
    transport: TransportType::Stdio,
    timeout: 10,
    command: 'php',
    args: ['server.php'],
    workingDir: __DIR__.'/../../server/samples/php_stdio',
);

$stdioClient = Client::make()
    ->withClientInfo($clientName, $clientVersion)
    ->withCapabilities($clientCapabilities)
    ->withLogger($logger)
    ->withServerConfig($stdioServerConfig)
    ->build();

try {
    echo "Attempting to initialize connection to {$stdioServerConfig->name}...\n";

    $stdioClient->initialize();

    echo "Connection to {$stdioServerConfig->name} initialized successfully!\n";
    echo "Server: {$stdioClient->getServerName()} v{$stdioClient->getServerVersion()}, Protocol: {$stdioClient->getNegotiatedProtocolVersion()}\n";

    
    echo "\n[1] Listing Tools...\n";

    $tools = $stdioClient->listTools();

    if (empty($tools)) {
        echo "   No tools found on the server.\n";
    } else {
        echo "   Available Tools:\n";
        foreach ($tools as $tool) {
            echo "   - {$tool->name}".($tool->description ? " ({$tool->description})" : '')."\n";
        }
    }

    
    echo "\n[2] Calling 'greeter' tool...\n";

    $toolName = 'greeter';
    $arguments = ['name' => 'Kyrian', 'count' => 2];

    try {
        $callResult = $stdioClient->callTool($toolName, $arguments);

        if ($callResult->isSuccess()) {
            $textContent = $callResult->content[0] ?? null;
            echo "   Tool '{$toolName}' Result: ".$textContent->text."\n";
        } else {
            $errorContent = $callResult->content[0] ?? null;
            $errorMessage = ($errorContent instanceof TextContent) ? $errorContent->text : 'Unknown tool error format';
            echo "   Tool '{$toolName}' reported an error: {$errorMessage}\n";
        }
    } catch (RequestException $e) {
        if ($e->getRpcError()) {
            echo "   Error calling tool '{$toolName}': Server Error Code {$e->getRpcError()->code} - {$e->getRpcError()->message}\n";
        } else {
            echo "   Error calling tool '{$toolName}': {$e->getMessage()}\n";
        }
    } catch (McpClientException $e) {
        echo "   Error calling tool '{$toolName}': {$e->getMessage()}\n";
    }

    
    echo "\n[3] Listing Resources...\n";

    $resources = $stdioClient->listResources();

    if (empty($resources)) {
        echo "   No resources found on the server.\n";
    } else {
        echo "   Available Resources:\n";
        foreach ($resources as $resource) {
            echo "   - {$resource->uri}".($resource->name ? " (Name: {$resource->name})" : '')."\n";
        }
    }

    
    $resourceUri = 'user://data';
    echo "\n[4] Reading resource '{$resourceUri}'...\n";

    try {
        $readResult = $stdioClient->readResource($resourceUri);
        $resourceContent = $readResult->contents[0] ?? null;

        if ($resourceContent instanceof EmbeddedResource) {
            echo "   Resource MIME Type: {$resourceContent->mimeType}\n";
            echo '   Resource Content: '.($resourceContent->text ?? '[Binary Data]')."\n";
        } else {
            echo "   Resource '{$resourceUri}' not found, empty, or invalid format.\n";
        }
    } catch (RequestException $e) {
        if ($e->getRpcError()) {
            echo "   Error reading resource '{$resourceUri}': Server Error Code {$e->getRpcError()->code} - {$e->getRpcError()->message}\n";
        } else {
            echo "   Error reading resource '{$resourceUri}': {$e->getMessage()}\n";
        }
    } catch (McpClientException $e) {
        echo "   Error reading resource '{$resourceUri}': {$e->getMessage()}\n";
    }

    
    echo "\n[5] Pinging server...\n";

    try {
        $stdioClient->ping();

        echo "   Ping successful!\n";
    } catch (McpClientException $e) {
        echo "   Ping failed: {$e->getMessage()}\n";
    }

} catch (McpClientException $e) {
    echo "\n MCP Client Error: ".get_class($e).' - '.$e->getMessage()."\n";
} catch (\Throwable $e) {
    echo "\n Unexpected Error: ".get_class($e).' - '.$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
} finally {
    
    echo "\nDisconnecting from server '{$stdioServerConfig->name}'...\n";
    $stdioClient->disconnect();
    echo "Disconnected.\n";
}
