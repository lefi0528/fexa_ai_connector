<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Services;

use PrestaShop\Module\FexaAiConnector\Repository\McpToolsRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class McpToolsService
{
    private $mcpToolsRepository;

    public function __construct(McpToolsRepository $mcpToolsRepository)
    {
        $this->mcpToolsRepository = $mcpToolsRepository;
    }

    public function registerTool(int $moduleId, string $toolName, string $toolDescription): bool
    {
        $success = true;

        $result = $this->mcpToolsRepository->upsertTool(
            $moduleId,
            $toolName,
            $toolDescription,
            true
        );

        if (!$result) {
            $success = false;
        }

        return $success;
    }

    public function getAllTools(): array
    {
        $tools = $this->mcpToolsRepository->getAllTools();

        $toolsGroupedByModule = [];
        foreach ($tools as $tool) {
            $module = \Module::getInstanceById($tool['module_id']);

            if (!$module) {
                throw new \PrestaShopException('Module is not defined');
            }

            $moduleName = $module->displayName;
            $moduleId = $module->id;

            if (!isset($toolsGroupedByModule[$moduleName])) {
                $toolsGroupedByModule[$moduleName] = [
                    'module_name' => $moduleName,
                    'tools' => [],
                    'module_id' => $moduleId,
                ];
            }
            $toolsGroupedByModule[$moduleName]['tools'][] = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'is_active' => (bool) $tool['is_active'],
                'module_id' => $tool['module_id'],
            ];
        }

        return array_values($toolsGroupedByModule);
    }

    public function getToolByNameAndModuleId(string $toolName, int $moduleId): array|false
    {
        return $this->mcpToolsRepository->getToolByNameAndModuleId($toolName, $moduleId);
    }

    public function updateToolsStatus(array $toolsList, bool $isActive): int
    {
        $updatedCount = 0;

        foreach ($toolsList as $tool) {
            if ($this->mcpToolsRepository->updateToolStatus((int) $tool['moduleId'], $tool['name'], $isActive)) {
                ++$updatedCount;
            }
        }

        return $updatedCount;
    }

    public function deleteToolByNameAndModuleId(string $toolName, int $moduleId): bool
    {
        return $this->mcpToolsRepository->deleteToolByNameAndModuleId($toolName, $moduleId);
    }

    public function deleteAllToolsByModuleId(int $moduleId): bool
    {
        return $this->mcpToolsRepository->deleteAllToolsByModuleId($moduleId);
    }

    public function cleanObsoleteTools(array $discoveredTools): bool
    {
        $allToolsFromDb = $this->mcpToolsRepository->getAllTools();

        $discoveredToolsNames = [];
        foreach ($discoveredTools as $tool) {
            $discoveredToolsNames[] = $tool['name'];
        }

        foreach ($allToolsFromDb as $toolFromDb) {
            if (!in_array($toolFromDb['name'], $discoveredToolsNames)) {
                $this->mcpToolsRepository->deleteToolByNameAndModuleId($toolFromDb['name'], (int) $toolFromDb['module_id']);
            }
        }

        return true;
    }
}
