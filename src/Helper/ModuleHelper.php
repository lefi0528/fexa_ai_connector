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

class ModuleHelper
{
    public function buildModuleInformation(string $technicalName, ?string $nameIfMissing = null): array
    {
        $baseUrl = \Tools::getShopDomainSsl(true, true);

        return [
            'technicalName' => $technicalName,
            'name' => $this->isInstalled($technicalName) ? \ModuleCore::getModuleName($technicalName) : ($nameIfMissing ?? 'Unknown Module'),
            'logo' => $this->isInstalled($technicalName) ? $baseUrl . __PS_BASE_URI__ . 'modules/' . $technicalName . '/logo.png' : $baseUrl . __PS_BASE_URI__ . 'modules/fexa_ai_connector/logo.png',
            'installState' => $this->isInstalled($technicalName)
                ? ($this->isEnabled($technicalName) ? 'installed' : 'disabled')
                : 'uninstalled',
            'version' => $this->getModuleVersion($technicalName),
        ];
    }

    private function isInstalled(string $moduleName)
    {
        return \ModuleCore::isInstalled($moduleName);
    }

    private function isEnabled(string $moduleName)
    {
        return \ModuleCore::isEnabled($moduleName);
    }

    private function getModuleVersion(string $moduleName)
    {
        if (false === $this->isInstalled($moduleName)) {
            return '0.0.0';
        }

        $module = \Module::getInstanceByName($moduleName);

        if (false === $module) {
            return '0.0.0';
        }

        return $module->version;
    }
}
