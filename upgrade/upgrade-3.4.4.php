<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * Upgrade to 3.4.4 — robustness for PrestaShop 9.x: the MCP endpoint no longer
 * returns a blank HTTP 500 on failure. It now (1) checks the module's `.mcp`
 * directory is writable and returns an actionable message otherwise, and
 * (2) wraps the whole request in a try/catch that surfaces the real error as a
 * JSON-RPC error instead of a fatal. This upgrade clears any stale discovery
 * cache so the first request rebuilds it cleanly.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Fexa_ai_connector $module
 *
 * @return bool
 */
function upgrade_module_3_4_4($module)
{
    // Drop any half-written discovery cache so it is regenerated on the next call.
    $cacheFile = _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp/.cache_v2.json';
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }

    // Make sure the writable working directory exists.
    $cacheDir = _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
    Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);

    return true;
}
