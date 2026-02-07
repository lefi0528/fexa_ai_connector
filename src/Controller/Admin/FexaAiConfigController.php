<?php
/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Controller\Admin;

use PrestaShop\Module\FexaAiConnector\Exceptions\ContextException;
use PrestaShop\Module\FexaAiConnector\Helper\ModuleHelper;
use PrestaShop\Module\FexaAiConnector\Services\McpService;
use PrestaShop\Module\FexaAiConnector\Services\McpToolsService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FexaAiConfigController extends FrameworkBundleAdminController
{
    public function index(Request $request): Response
    {
        return $this->render('@Modules/fexa_ai_connector/views/templates/admin/base.html.twig', [
            'layoutTitle' => 'Fexa AI - MCP Configuration',
            'mcp_api_key' => \Configuration::get('FEXA_AI_API_KEY'),
        ]);
    }
}
