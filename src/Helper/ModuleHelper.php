<?php

/**
 * Copyright (c) 2025 PrestaShop SA
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by PrestaShop SA. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by PrestaShop SA.
 *
 * The PS MCP Server module was developed by PrestaShop, which holds all associated intellectual property rights. The license granted to the user does not entail any transfer of rights. The user shall refrain from any act that may infringe upon PrestaShop's rights and undertakes to strictly comply with the limitations of the license set out below. PrestaShop grants the user a personal, non-exclusive, non-transferable, and non-sublicensable license to use the MCP Server module, worldwide and for the entire duration of use of the module. This license is strictly limited to installing the module and using it solely for the operation of the user's PrestaShop store.
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
            'logo' => $this->isInstalled($technicalName) ? $baseUrl . __PS_BASE_URI__ . 'modules/' . $technicalName . '/logo.png' : $baseUrl . __PS_BASE_URI__ . 'modules/ps_mcp_server/views/img/ps_logo.jpeg',
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
