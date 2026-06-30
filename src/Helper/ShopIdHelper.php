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

namespace PrestaShop\Module\FexaAiConnector\Helper;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShopIdHelper
{
    public static function getShopId(): ?string
    {
        $accountModule = \Module::getInstanceByName('ps_accounts');

        if (!$accountModule) {
            return null;
        }

        $accountService = $accountModule->getService('PrestaShop\Module\PsAccounts\Service\PsAccountsService');

        if (!$accountService || !is_object($accountService) || !method_exists($accountService, 'getShopUuidV4')) {
            return null;
        }

        return $accountService->getShopUuidV4();
    }
}
