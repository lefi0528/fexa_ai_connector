<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Repository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class McpModulesRegisteredRepository
{
    public const TABLE_NAME = 'mcp_server_modules_registered';

    private $db;

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    public function getAllModules(): array
    {
        $query = new \DbQuery();
        $query->select('*')
            ->from(self::TABLE_NAME, 'mcp_smr')
            ->orderBy('mcp_smr.created_at DESC');

        $result = $this->db->executeS($query);

        return is_array($result) ? $result : [];
    }

    public function addModuleId(int $moduleId): bool
    {
        $query = new \DbQuery();

        $query->select('*')
            ->from(self::TABLE_NAME, 'mcp_smr')
            ->where("mcp_smr.module_id = '" . (int) $moduleId . "'");

        $existingModule = (array) $this->db->executeS($query);

        if (count($existingModule) > 0) {
            return false;
        }

        $data = [
            'module_id' => (int) $moduleId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->db->insert(self::TABLE_NAME, $data);
    }

    public function getModuleById(int $moduleId): array|false
    {
        $query = new \DbQuery();

        $query->select('*')
            ->from(self::TABLE_NAME, 'mcp_smr')
            ->where('mcp_smr.module_id = ' . (int) $moduleId);

        $result = $this->db->getRow($query);

        return is_array($result) ? $result : false;
    }

    public function deleteModuleById(int $moduleId): bool
    {
        return $this->db->delete(self::TABLE_NAME, 'module_id = "' . (int) $moduleId . '"');
    }
}
