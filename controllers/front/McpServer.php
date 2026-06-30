<?php
/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI.
 *
 * @author    Fexa AI <support@fexaai.com>
 * @copyright 2025 Fexa AI
 * @license   Proprietary
 */
use PrestaShop\Module\FexaAiConnector\Services\McpService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fexa_ai_connectorMcpServerModuleFrontController extends ModuleFrontController
{
    /** @var Fexa_ai_connector */
    public $module;

    public function initContent(): void
    {
        // Restrict CORS to fexaai.com only
        $allowedOrigins = ['https://fexaai.com', 'https://www.fexaai.com'];
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        if (in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-MCP-API-KEY, Mcp-Protocol-Version, Mcp-Session-Id, Last-Event-ID');

        // Handle preflight
        if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
            http_response_code(204);
            exit;
        }

        // Security: Verify API Key from header or query string
        $storedKey = Configuration::get('FEXA_AI_API_KEY');

        $apiKey = '';
        // 1. Prefer Authorization header: Bearer <token>
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if (preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $matches)) {
            $apiKey = $matches[1];
        }
        // 2. Fallback: X-MCP-API-KEY header
        if (empty($apiKey) && !empty($_SERVER['HTTP_X_MCP_API_KEY'])) {
            $apiKey = $_SERVER['HTTP_X_MCP_API_KEY'];
        }
        // 3. Fallback: token or api_key query param (legacy)
        if (empty($apiKey)) {
            // Cast to string: Tools::getValue returns false when absent, and passing
            // false to hash_equals() is a fatal TypeError on PHP 8 (the endpoint would
            // return HTTP 200 with a PHP stack trace instead of a clean 403 JSON).
            $apiKey = (string) (Tools::getValue('token') ?: Tools::getValue('api_key'));
        }

        if (!$storedKey || !hash_equals($storedKey, $apiKey)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid API Key']);
            exit;
        }

        $mcpService = $this->module->getService(McpService::class);

        // Fallback: some PrestaShop builds (and stale compiled containers) don't
        // resolve a service by its FQCN when the DI id differs. Try the concrete id.
        if (!$mcpService instanceof McpService) {
            $mcpService = $this->module->getService('fexa_ai_connector.mcp_service');
        }

        if (!$mcpService instanceof McpService) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Le service MCP du module n\'a pas pu être initialisé. '
                        . 'Videz le cache de PrestaShop (Paramètres avancés → Performances → Vider le cache), '
                        . 'puis réessayez. Si le problème persiste, vérifiez que le dossier var/cache est '
                        . 'inscriptible par le serveur web.',
                ],
                'id' => null,
            ]);
            exit;
        }

        $mcpService->executeHttpMcpRequest();
    }
}
