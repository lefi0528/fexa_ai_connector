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
function upgrade_module_3_4_5($module)
{
    // 1) Delete the stale compiled DI containers so they are rebuilt with the new
    //    FQCN alias. They live in the PrestaShop cache dir (prod and dev variants).
    $candidates = [];
    if (defined('_PS_CACHE_DIR_')) {
        $candidates[] = rtrim((string) constant('_PS_CACHE_DIR_'), '/\\');
    }
    if (defined('_PS_ROOT_DIR_')) {
        $root = rtrim((string) constant('_PS_ROOT_DIR_'), '/\\');
        $candidates[] = $root . '/var/cache/prod';
        $candidates[] = $root . '/var/cache/dev';
    }

    $names = [
        'Fexa_ai_connectorFrontContainer.php',
        'Fexa_ai_connectorAdminContainer.php',
    ];

    foreach (array_unique($candidates) as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach ($names as $name) {
            $file = $dir . '/' . $name;
            if (is_file($file)) {
                @unlink($file);
            }
            // Symfony also writes a .php.meta sidecar alongside the dumped container.
            if (is_file($file . '.meta')) {
                @unlink($file . '.meta');
            }
        }
    }

    // 2) Clear any half-written MCP discovery cache and re-arm discovery.
    $cacheFile = _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp/.cache_v2.json';
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
    $cacheDir = _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
    Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);

    return true;
}
