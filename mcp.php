<?php
/**
 * Universal Entry Point for SEO Copilot MCP Server
 * Dispatches to Legacy (PHP < 8.1) or Modern (PHP >= 8.1) implementation.
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

// Check PHP Version and Dispatch
if (PHP_VERSION_ID < 80100) {
    // --- LEGACY MODE (PHP < 8.1) ---
    require_once dirname(__FILE__) . '/src/Legacy/Server.php';
    require_once dirname(__FILE__) . '/src/Legacy/Tools/ProductTool.php';

    $server = new \PrestaShop\Module\FexaAiConnector\Legacy\Server();
    $server->handleRequest(function() {
        return file_get_contents('php://input');
    });

} else {
    // --- MODERN MODE (PHP >= 8.1) ---
    // Use the official Service Container to load the MCP Service
    $module = Module::getInstanceByName('fexa_ai_connector');
    
    if (!$module) {
        header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => 'Module not found']);
        exit;
    }

    try {
        // We use the string class name to avoid importing it here (which might trigger autoload/issues on old PHP if file was parsed, though inside else it's safe)
        $serviceName = 'PrestaShop\\Module\\FexaAiConnector\\Services\\McpService';
        $mcpService = $module->getService($serviceName);
        
        if ($mcpService) {
            $mcpService->executeHttpMcpRequest();
        } else {
            throw new Exception('McpService could not be loaded');
        }
    } catch (Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => 'Modern Server Error: ' . $e->getMessage()]);
        exit;
    }
}
