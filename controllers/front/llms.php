<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI.
 *
 * Serves the shop's /llms.txt at the domain root from the fexa_ai_llms_txt table.
 * READ-ONLY: it only SELECTs the stored content and echoes it — it never writes
 * anything and never affects any other page. No content stored → 404.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fexa_ai_connectorLlmsModuleFrontController extends ModuleFrontController
{
    /** Public machine file — no customer authentication. */
    public $auth = false;

    public function initContent(): void
    {
        $idShop = (int) $this->context->shop->id;

        // Prefer a shop-specific row, fall back to the all-shops (0) row.
        $row = Db::getInstance()->getRow(
            'SELECT content FROM `' . _DB_PREFIX_ . 'fexa_ai_llms_txt` '
            . 'WHERE is_active = 1 AND (id_shop = ' . $idShop . ' OR id_shop = 0) '
            . 'ORDER BY id_shop DESC LIMIT 1'
        );

        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex');

        if (!is_array($row) || empty($row['content'])) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        header('Cache-Control: public, max-age=86400');
        echo $row['content'];
        exit;
    }
}
