<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 *
 * This module was developed by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Controller\Admin;

use PrestaShop\Module\FexaAiConnector\Exceptions\ContextException;
use PrestaShop\Module\FexaAiConnector\Helper\ModuleHelper;
use PrestaShop\Module\FexaAiConnector\Services\McpService;
use PrestaShop\Module\FexaAiConnector\Services\McpToolsService;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class McpServerConfigController extends FrameworkBundleAdminController
{
    private \Fexa_ai_connector $module;

    public function __construct()
    {
        $module = \Module::getInstanceByName('fexa_ai_connector');

        $this->module = $module;

        if ((bool) \Configuration::get('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE') && (bool) \Configuration::get('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER')) {
            $mcpService = $this->module->getService(McpService::class);
            $mcpService->discover();
        }
    }

    public function index(Request $request): Response
    {
        $this->handleFirstDiscovery();

        return $this->render('@Modules/fexa_ai_connector/views/templates/admin/base.html.twig', [
            'layoutTitle' => 'Fexa AI - MCP Configuration',
            'mcp_api_key' => \Configuration::get('FEXA_AI_API_KEY'),
        ]);
    }

    private function handleFirstDiscovery(): void
    {
        $firstConfigDone = (bool) \Configuration::get('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE');

        $module = \Module::getInstanceByName('fexa_ai_connector');

        if (!$module->tableExist('mcp_server_modules_registered') || !$module->tableExist('mcp_server_tools') || !$module->tableExist('mcp_server_allowed_users')) {
            $module->installDatabaseTables();
            $firstConfigDone = false;
        }

        if (!$firstConfigDone) {
            try {
                $mcpService = $this->module->getService(McpService::class);
                if ($mcpService != null) {
                    $mcpService->fetchAllModulesCompliantWithMcp();
                    $mcpService->discover();
                    \Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', true);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error during automatic discovery: ' . $e->getMessage());
            }
        }
    }
}
