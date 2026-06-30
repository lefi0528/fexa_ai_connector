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

namespace PrestaShop\Module\FexaAiConnector\Services;

use PrestaShop\Module\FexaAiConnector\Repository\McpModulesRegisteredRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class McpModulesRegisteredService
{
    private $repository;

    public function __construct(McpModulesRegisteredRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllModules(): array
    {
        return $this->repository->getAllModules();
    }

    public function addModuleId(int $moduleId): bool
    {
        return $this->repository->addModuleId($moduleId);
    }

    public function getModuleById(int $moduleId): array|false
    {
        return $this->repository->getModuleById($moduleId);
    }

    public function deleteModuleById(int $moduleId): bool
    {
        return $this->repository->deleteModuleById($moduleId);
    }
}
