<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * Upgrade to 3.4.0 — adds the structured-data delivery channel:
 *  - creates the `fexa_ai_structured_data` table,
 *  - registers the `displayHeader` hook (renders JSON-LD into <head>),
 *  - forces MCP re-discovery so the new tools (set/get/delete_structured_data) appear.
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
