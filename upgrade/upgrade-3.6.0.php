<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * Upgrade to 3.6.0 — adds the store /llms.txt feature:
 *  - new `fexa_ai_llms_txt` table (machine-readable AEO map stored by the SaaS),
 *  - the `set_llms_txt` / `get_llms_txt` / `delete_llms_txt` MCP tools,
 *  - the `/llms.txt` front route served from that table.
 * Purely additive: creates a new table + advertises new tools. No existing table,
 * tool, route or page is changed.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Fexa_ai_connector $module
 *
 * @return bool
 */
function upgrade_module_3_6_0($module)
{
    // 1. Create the llms.txt storage table (existing installs don't have it).
    Db::getInstance()->execute(
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fexa_ai_llms_txt` ('
        . '`id` INT(10) UNSIGNED AUTO_INCREMENT,'
        . '`id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 0,'
        . '`id_lang` INT(10) UNSIGNED NOT NULL DEFAULT 0,'
        . '`content` LONGTEXT NOT NULL,'
        . '`is_active` TINYINT(1) NOT NULL DEFAULT 1,'
        . '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
        . 'PRIMARY KEY (`id`),'
        . 'UNIQUE KEY `uq_shop_lang` (`id_shop`,`id_lang`)'
        . ') ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
    );

    // 2. Force MCP re-discovery so the new set_llms_txt tool is advertised to the SaaS.
    $cacheFile = _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp/.cache_v2.json';
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
    Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
    Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);

    // 3. The /llms.txt route lives in the already-registered moduleRoutes hook; clear
    //    caches so the dispatcher picks it up.
    if (method_exists('Tools', 'clearSmartyCache')) {
        Tools::clearSmartyCache();
    }

    return true;
}
