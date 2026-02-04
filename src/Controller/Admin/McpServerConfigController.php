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

namespace PrestaShop\Module\PsMcpServer\Controller\Admin;

use PrestaShop\Module\PsMcpServer\Exceptions\ContextException;
use PrestaShop\Module\PsMcpServer\Helper\ModuleHelper;
use PrestaShop\Module\PsMcpServer\Services\McpService;
use PrestaShop\Module\PsMcpServer\Services\McpToolsService;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class McpServerConfigController extends FrameworkBundleAdminController
{
    private \Ps_mcp_server $module;

    public function __construct()
    {
        $module = \Module::getInstanceByName('ps_mcp_server');

        $this->module = $module;

        if ((bool) \Configuration::get('PS_MCP_SERVER_FIRST_DISCOVERY_DONE') && (bool) \Configuration::get('PS_MCP_SERVER_TOOLS_NEED_DISCOVER')) {
            $mcpService = $this->module->getService(McpService::class);
            $mcpService->discover();
        }
    }

    public function index(Request $request): Response
    {
        $this->handleFirstDiscovery();

        return $this->render('@Modules/ps_mcp_server/views/templates/admin/base.html.twig', [
            'layoutTitle' => 'SEO Copilot - MCP Configuration',
            'mcp_api_key' => \Configuration::get('PS_MCP_API_KEY'),
        ]);
    }

    private function handleFirstDiscovery(): void
    {
        $firstConfigDone = (bool) \Configuration::get('PS_MCP_SERVER_FIRST_DISCOVERY_DONE');

        $module = \Module::getInstanceByName('ps_mcp_server');

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
                    \Configuration::updateValue('PS_MCP_SERVER_FIRST_DISCOVERY_DONE', true);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error during automatic discovery: ' . $e->getMessage());
            }
        }
    }
}
