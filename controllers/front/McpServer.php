<?php

/**
 * Copyright (c) 2025 PrestaShop SA
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by PrestaShop SA. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by PrestaShop SA.
 *
 * The PS MCP Server module was developed by PrestaShop, which holds all associated intellectual property rights. The license granted to the user does not entail any transfer of rights. The user shall refrain from any act that may infringe upon PrestaShop's rights and undertakes to strictly comply with the limitations of the license set out below. PrestaShop grants the user a personal, non-exclusive, non-transferable, and non-sublicensable license to use the MCP Server module, worldwide and for the entire duration of use of the module. This license is strictly limited to installing the module and using it solely for the operation of the user's PrestaShop store.
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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Mcp-Protocol-Version, Mcp-Session-Id, Last-Event-ID');

        $mcpService = $this->module->getService(McpService::class);

        if ($mcpService == null) {
            throw new PrestaShopException('McpService is not available');
        }

        $mcpService->executeHttpMcpRequest();
    }
}
