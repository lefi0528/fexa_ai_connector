<?php
/**
 * Entry Point for Fexa AI MCP Server (PHP 8.1+ / PrestaShop 8+)
 */

// Load PrestaShop Configuration
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';

// Security: Verify API Key
$apiKey = Tools::getValue('api_key');
$storedKey = Configuration::get('FEXA_AI_API_KEY');

if (!$storedKey || $apiKey !== $storedKey) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// Load MCP Service via Service Container
$module = Module::getInstanceByName('fexa_ai_connector');

if (!$module) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['error' => 'Module not found']);
    exit;
}

try {
    $serviceName = 'PrestaShop\\Module\\FexaAiConnector\\Services\\McpService';
    $mcpService = $module->getService($serviceName);
    
    if ($mcpService) {
        $mcpService->executeHttpMcpRequest();
    } else {
        throw new Exception('McpService could not be loaded');
    }
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
    exit;
}
