<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * Upgrade to 3.4.6 — adds the `count_catalog` MCP tool (exact product / category
 * / CMS totals via lightweight COUNT queries) so the SaaS can show an accurate
 * "all catalogue" scan estimate instead of approximating from category counts.
 * Forces MCP re-discovery so the new tool is advertised to clients.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Fexa_ai_connector $module
 *
 * @return bool
 */
function upgrade_module_3_4_6($module)
{
    // Drop any cached tool list so the new count_catalog tool is registered.
    $cacheFile = _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp/.cache_v2.json';
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }

    Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
    Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);

    return true;
}
