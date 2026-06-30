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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Fexa_ai_connector $module
 *
 * @return bool
 */
function upgrade_module_3_4_0($module)
{
    // install.sql uses CREATE TABLE IF NOT EXISTS — safe to re-run on existing installs.
    $ok = $module->installDatabaseTables();

    if (!$module->isRegisteredInHook('displayHeader')) {
        $ok = $ok && $module->registerHook('displayHeader');
    }

    // New MCP tools live in src/Mcp/Tools — trigger a fresh discovery pass.
    Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);

    return (bool) $ok;
}
