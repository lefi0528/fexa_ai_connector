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

class McpToolsRepository
{
    public const TABLE_NAME = 'mcp_server_tools';
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    private $db;

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    public function deleteToolByNameAndModuleId(string $toolName, int $moduleId): bool
    {
        return $this->db->delete(self::TABLE_NAME, 'name = "' . pSQL($toolName) . '" AND module_id = ' . (int) $moduleId);
    }

    public function deleteAllToolsByModuleId(int $moduleId): bool
    {
        return $this->db->delete(self::TABLE_NAME, 'module_id = ' . (int) $moduleId);
    }

    public function upsertTool(int $moduleId, string $name, string $description, bool $isActive = true): bool
    {
        $data = [
            'module_id' => (int) $moduleId,
            'name' => pSQL($name),
            'description' => pSQL($description),
            'is_active' => $isActive ? 1 : 0,
            'created_at' => date(self::DATETIME_FORMAT),
            'updated_at' => date(self::DATETIME_FORMAT),
        ];

        $existingTool = $this->getToolByNameAndModuleId($name, $moduleId);

        if (is_array($existingTool)) {
            unset($data['created_at']);

            return $this->db->update(self::TABLE_NAME, $data, 'id = ' . (int) $existingTool['id']);
        } else {
            return $this->db->insert(self::TABLE_NAME, $data);
        }
    }

    public function updateToolStatus(int $moduleId, string $toolName, bool $isActive): bool
    {
        $data = [
            'is_active' => $isActive ? 1 : 0,
            'updated_at' => date(self::DATETIME_FORMAT),
        ];

        return $this->db->update(
            self::TABLE_NAME,
            $data,
            'module_id = ' . (int) $moduleId . ' AND name = \'' . pSQL($toolName) . '\''
        );
    }

    public function getToolByNameAndModuleId(string $name, int $moduleId): array|false
    {
        $query = new \DbQuery();
        $query->select('*')
            ->from(self::TABLE_NAME, 'mcp_st')
            ->where('mcp_st.module_id = ' . (int) $moduleId)
            ->where('mcp_st.name = "' . pSQL($name) . '"');

        $result = $this->db->getRow($query);

        return is_array($result) ? $result : false;
    }

    public function getAllTools(): array
    {
        $query = new \DbQuery();
        $query->select('*')
            ->from(self::TABLE_NAME);

        $result = $this->db->executeS($query);

        return is_array($result) ? $result : [];
    }
}
