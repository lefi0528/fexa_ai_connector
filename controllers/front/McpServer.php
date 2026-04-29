<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

use PrestaShop\Module\FexaAiConnector\Services\McpService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fexa_ai_connectorMcpServerModuleFrontController extends ModuleFrontController
{
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
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Security: Verify API Key from header or query string
        $storedKey = \Configuration::get('FEXA_AI_API_KEY');

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
            $apiKey = \Tools::getValue('token') ?: \Tools::getValue('api_key');
        }

        if (!$storedKey || !hash_equals($storedKey, $apiKey)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid API Key']);
            exit;
        }

        $mcpService = $this->module->getService(McpService::class);

        if ($mcpService === null) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'McpService is not available']);
            exit;
        }

        $mcpService->executeHttpMcpRequest();
    }
}
