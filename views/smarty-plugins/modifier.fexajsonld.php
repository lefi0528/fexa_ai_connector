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
 * Smarty modifier: make a JSON-LD payload safe to embed inside a ld+json script
 * element by neutralising the only breakout vector — a premature closing sequence.
 *
 * HTML-escaping is deliberately NOT used: a ld+json script's content is raw text the
 * browser never HTML-decodes, so entity-encoding would corrupt the JSON for crawlers.
 * Replacing "</" with "<\/" keeps the JSON valid (\/ is a valid JSON escape for /)
 * while making a premature closing sequence impossible.
 *
 * @param string $json
 *
 * @return string
 */
function smarty_modifier_fexajsonld($json)
{
    return str_replace('</', '<\\/', (string) $json);
}
