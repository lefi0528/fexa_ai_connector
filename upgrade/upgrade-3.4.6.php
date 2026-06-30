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
function upgrade_module_3_4_6($module)
{
    // Drop any cached tool list so the new count_catalog tool is registered.
    $cacheFile = _PS_MODULE_DIR_.'fexa_ai_connector/.mcp/.cache_v2.json';
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }

    Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
    Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);

    return true;
}
