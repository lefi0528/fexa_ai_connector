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

namespace PrestaShop\Module\FexaAiConnector\Controller\Admin;

use Configuration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FexaAiConfigController extends AbstractFexaAdminController
{
    public function index(Request $request): Response
    {
        return $this->render('@Modules/fexa_ai_connector/views/templates/admin/base.html.twig', [
            'layoutTitle' => 'Fexa AI - MCP Configuration',
            'mcp_api_key' => Configuration::get('FEXA_AI_API_KEY'),
        ]);
    }
}
