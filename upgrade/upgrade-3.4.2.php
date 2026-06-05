<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * Upgrade to 3.4.2 — translatable name & URL slug:
 *  - update_product_seo / update_category_seo now accept `name` and `link_rewrite`,
 *  - update_cms_seo now accepts `link_rewrite`,
 *  so translations also localize the product/category title and the URL slug.
 *  Forces MCP re-discovery so the new tool parameters are picked up.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Fexa_ai_connector $module
 *
 * @return bool
 */
function upgrade_module_3_4_2($module)
{
    // Tool signatures changed (new params) — trigger a fresh discovery pass so the
    // updated schemas are advertised to the SaaS.
    Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
    Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);

    return true;
}
