<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 */

namespace PrestaShop\Module\FexaAiConnector\Helper;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Defense-in-depth sanitiser for content written by the update_*_seo tools.
 *
 * The Fexa AI SaaS already sanitises HTML before sending, but cleaning here too
 * means the module never writes unsafe markup to the shop regardless of caller.
 * Uses PrestaShop's bundled HTMLPurifier (Tools::purifyHTML) for rich HTML.
 */
class HtmlSanitizer
{
    /** Sanitise rich HTML (description / content) — strips scripts/styles, balances tags. */
    public static function richHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        return trim((string) \Tools::purifyHTML($html));
    }

    /** Plain text + length cap for meta / short fields (HTML in meta breaks save()). */
    public static function meta(string $text, int $maxLen): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));

        if (function_exists('mb_strlen')) {
            return mb_strlen($text) > $maxLen ? trim(mb_substr($text, 0, $maxLen)) : $text;
        }

        return strlen($text) > $maxLen ? trim(substr($text, 0, $maxLen)) : $text;
    }
}
