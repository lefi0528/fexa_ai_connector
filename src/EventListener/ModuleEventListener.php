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

namespace PrestaShop\Module\FexaAiConnector\EventListener;

use Exception;
use Module;
use PrestaShop\Module\FexaAiConnector\Services\McpService;
use PrestaShopBundle\Event\ModuleManagementEvent;
use PrestaShopException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModuleEventListener implements EventSubscriberInterface
{
    private McpService $mcpService;

    public function __construct(McpService $mcpService)
    {
        $this->mcpService = $mcpService;
    }

    public static function getSubscribedEvents()
    {
        return [
            ModuleManagementEvent::INSTALL => 'storeNewModuleRegistered',
            ModuleManagementEvent::UNINSTALL => 'removeModuleRegistered',
            ModuleManagementEvent::DISABLE => 'removeModuleRegistered',
            ModuleManagementEvent::ENABLE => 'storeNewModuleRegistered',
            ModuleManagementEvent::UPGRADE => 'storeNewModuleRegistered',
        ];
    }

    public function storeNewModuleRegistered(ModuleManagementEvent $event): void
    {
        try {
            $module = Module::getInstanceByName($event->getModule()->get('name'));

            if (!$module) {
                throw new PrestaShopException('Module not found');
            }

            if (method_exists($module, 'isMcpCompliant') && $module->isMcpCompliant()) {
                $this->mcpService->storeNewModuleRegistered((int) $module->id);
            }
        } catch (Exception $e) {
            throw new PrestaShopException('Error while registering module to MCP: '.$e->getMessage());
        }
    }

    public function removeModuleRegistered(ModuleManagementEvent $event): void
    {
        try {
            $module = Module::getInstanceByName($event->getModule()->get('name'));

            if (!$module) {
                throw new PrestaShopException('Module not found');
            }

            if (method_exists($module, 'isMcpCompliant') && $module->isMcpCompliant()) {
                $this->mcpService->removeModuleRegistered((int) $module->id);
            }
        } catch (Exception $e) {
            throw new PrestaShopException('Error while removing module from MCP: '.$e->getMessage());
        }
    }
}
