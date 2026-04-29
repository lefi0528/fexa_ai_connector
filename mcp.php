<?php
/**
 * Entry Point for Fexa AI MCP Server (PHP 8.1+ / PrestaShop 8+)
 *
 * @deprecated This file is kept for backward compatibility only.
 *             The canonical endpoint is the McpServer front controller:
 *             /index.php?fc=module&module=fexa_ai_connector&controller=McpServer
 */

// Load PrestaShop Configuration
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';

// Security: Verify API Key (support both 'token' and legacy 'api_key' params)
$apiKey = Tools::getValue('token') ?: Tools::getValue('api_key');
$storedKey = Configuration::get('FEXA_AI_API_KEY');

if (!$storedKey || !hash_equals($storedKey, (string) $apiKey)) {
    header('HTTP/1.0 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// Load MCP Service via Service Container
$module = Module::getInstanceByName('fexa_ai_connector');

if (!$module) {
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: application/json');
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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
    exit;
}
