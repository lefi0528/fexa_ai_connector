<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * Upgrade to 3.4.5 — fix "McpService is not available" (HTTP 500) when connecting
 * a shop. The MCP front controller resolved the service by its FQCN
 * (McpService::class), but the compiled Symfony container only registered it under
 * the id `fexa_ai_connector.mcp_service`, so get(FQCN) threw ServiceNotFound and
 * getService() returned null.
 *
 * We now (1) register an FQCN alias in config/common.yml and (2) make the
 * controller fall back to the concrete service id. Crucially, in production mode
 * Symfony's ConfigCache::isFresh() does NOT re-check the YAML when the dumped
 * container file already exists — so we must delete the stale compiled containers
 * here, otherwise the new alias is never picked up.
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
