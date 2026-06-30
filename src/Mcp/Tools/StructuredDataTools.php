<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Db;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Structured data (JSON-LD) storage tools.
 *
 * The JSON-LD is stored per entity/lang/shop and rendered into the page <head>
 * by the module's displayHeader hook — NOT injected into the (sanitised)
 * description field. This is the reliable delivery channel for FAQPage / Product
 * / BreadcrumbList / Speakable markup.
 */
class StructuredDataTools
{
    private const ALLOWED_ENTITIES = ['product', 'category', 'cms'];
    private const ALLOWED_SCHEMAS = ['FAQPage', 'QAPage', 'Product', 'BreadcrumbList', 'SpeakableSpecification'];
    private const MAX_BYTES = 50000;
    private const TABLE = 'fexa_ai_structured_data';

    #[McpTool(
        name: 'set_structured_data',
        description: 'Store a JSON-LD structured-data block for an entity. Rendered into the page <head> by the module (not the description field). Upserts by (entity, lang, shop, schema_type).'
    )]
    #[Schema(
        properties: [
            'entity_type' => ['type' => 'string', 'description' => "One of: 'product', 'category', 'cms'"],
            'entity_id' => ['type' => 'integer', 'description' => 'PrestaShop entity ID'],
            'schema_type' => ['type' => 'string', 'description' => "One of: 'FAQPage', 'QAPage', 'Product', 'BreadcrumbList', 'SpeakableSpecification'"],
            'jsonld' => ['type' => 'string', 'description' => 'Valid schema.org JSON-LD payload (as a JSON string)'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID (0 = all languages, default)'],
            'id_shop' => ['type' => 'integer', 'description' => 'Shop ID (0 = all shops, default)'],
        ],
        required: ['entity_type', 'entity_id', 'schema_type', 'jsonld']
    )]
    public function setStructuredData(
        string $entity_type,
        int $entity_id,
        string $schema_type,
        string $jsonld,
        ?int $id_lang = 0,
        ?int $id_shop = 0
    ): array {
        $id_lang = (int) $id_lang;
        $id_shop = (int) $id_shop;

        if (!in_array($entity_type, self::ALLOWED_ENTITIES, true)) {
            throw new \Exception("Invalid entity_type '$entity_type'");
        }
        if (!in_array($schema_type, self::ALLOWED_SCHEMAS, true)) {
            throw new \Exception("Invalid schema_type '$schema_type'");
        }
        if (!$this->entityExists($entity_type, $entity_id)) {
            throw new \Exception("$entity_type #$entity_id not found");
        }
        if (strlen($jsonld) > self::MAX_BYTES) {
            throw new \Exception('jsonld payload too large');
        }

        $decoded = json_decode($jsonld, true);
        if (!is_array($decoded)) {
            throw new \Exception('jsonld is not valid JSON');
        }
        $context = $decoded['@context'] ?? '';
        if (strpos((string) $context, 'schema.org') === false) {
            throw new \Exception('jsonld must declare an @context of schema.org');
        }
        if (empty($decoded['@type'])) {
            throw new \Exception('jsonld must declare an @type');
        }

        $db = Db::getInstance();
        $et = pSQL($entity_type);
        $st = pSQL($schema_type);
        $jl = pSQL($jsonld, true);

        $where = "entity_type='" . $et . "' AND entity_id=" . $entity_id
            . " AND id_lang=" . $id_lang . " AND id_shop=" . $id_shop
            . " AND schema_type='" . $st . "'";
        $db->delete(self::TABLE, $where);

        $db->execute(
            'INSERT INTO `' . _DB_PREFIX_ . self::TABLE . '` '
            . '(entity_type, entity_id, id_lang, id_shop, schema_type, jsonld, is_active) VALUES '
            . "('" . $et . "', " . $entity_id . ', ' . $id_lang . ', ' . $id_shop
            . ", '" . $st . "', '" . $jl . "', 1)"
        );

        $this->clearEntityCache();

        return [
            'status' => 'success',
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'schema_type' => $schema_type,
            'id_lang' => $id_lang,
            'id_shop' => $id_shop,
        ];
    }

    #[McpTool(
        name: 'get_structured_data',
        description: 'Read the stored JSON-LD blocks for an entity (for diff / delivery verification).'
    )]
    #[Schema(
        properties: [
            'entity_type' => ['type' => 'string', 'description' => "One of: 'product', 'category', 'cms'"],
            'entity_id' => ['type' => 'integer', 'description' => 'PrestaShop entity ID'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID filter (0 = any, default)'],
        ],
        required: ['entity_type', 'entity_id']
    )]
    public function getStructuredData(string $entity_type, int $entity_id, ?int $id_lang = 0): array
    {
        if (!in_array($entity_type, self::ALLOWED_ENTITIES, true)) {
            throw new \Exception("Invalid entity_type '$entity_type'");
        }
        $id_lang = (int) $id_lang;

        $sql = 'SELECT schema_type, jsonld, id_lang, id_shop, is_active FROM `'
            . _DB_PREFIX_ . self::TABLE . "` WHERE entity_type='" . pSQL($entity_type)
            . "' AND entity_id=" . $entity_id;
        if ($id_lang) {
            $sql .= ' AND (id_lang=' . $id_lang . ' OR id_lang=0)';
        }

        $rows = Db::getInstance()->executeS($sql);

        return [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'items' => is_array($rows) ? $rows : [],
        ];
    }

    #[McpTool(
        name: 'get_structured_data_bulk',
        description: 'Read which JSON-LD schema types are stored for many entities at once (one call per page). Returns metadata only (entity_id, schema_type, id_lang), not the payloads.'
    )]
    #[Schema(
        properties: [
            'entity_type' => ['type' => 'string', 'description' => "One of: 'product', 'category', 'cms'"],
            'entity_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'PrestaShop entity IDs (max 200)'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID filter (0 = any, default)'],
        ],
        required: ['entity_type', 'entity_ids']
    )]
    public function getStructuredDataBulk(string $entity_type, array $entity_ids, ?int $id_lang = 0): array
    {
        if (!in_array($entity_type, self::ALLOWED_ENTITIES, true)) {
            throw new \Exception("Invalid entity_type '$entity_type'");
        }
        $id_lang = (int) $id_lang;

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $entity_ids),
            function ($v) { return $v > 0; }
        )));
        $ids = array_slice($ids, 0, 200);
        if (count($ids) === 0) {
            return ['entity_type' => $entity_type, 'items' => []];
        }

        $sql = 'SELECT entity_id, schema_type, id_lang, id_shop FROM `'
            . _DB_PREFIX_ . self::TABLE . "` WHERE entity_type='" . pSQL($entity_type)
            . "' AND is_active=1 AND entity_id IN (" . implode(',', $ids) . ')';
        if ($id_lang) {
            $sql .= ' AND (id_lang=' . $id_lang . ' OR id_lang=0)';
        }

        $rows = Db::getInstance()->executeS($sql);

        return [
            'entity_type' => $entity_type,
            'items' => is_array($rows) ? $rows : [],
        ];
    }

    #[McpTool(
        name: 'delete_structured_data',
        description: 'Remove a stored JSON-LD block (used on rollback).'
    )]
    #[Schema(
        properties: [
            'entity_type' => ['type' => 'string', 'description' => "One of: 'product', 'category', 'cms'"],
            'entity_id' => ['type' => 'integer', 'description' => 'PrestaShop entity ID'],
            'schema_type' => ['type' => 'string', 'description' => 'Schema type to remove'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID (0 = all, default)'],
        ],
        required: ['entity_type', 'entity_id', 'schema_type']
    )]
    public function deleteStructuredData(string $entity_type, int $entity_id, string $schema_type, ?int $id_lang = 0): array
    {
        if (!in_array($entity_type, self::ALLOWED_ENTITIES, true)) {
            throw new \Exception("Invalid entity_type '$entity_type'");
        }
        $id_lang = (int) $id_lang;

        $where = "entity_type='" . pSQL($entity_type) . "' AND entity_id=" . $entity_id
            . " AND schema_type='" . pSQL($schema_type) . "'";
        if ($id_lang) {
            $where .= ' AND id_lang=' . $id_lang;
        }
        Db::getInstance()->delete(self::TABLE, $where);

        $this->clearEntityCache();

        return ['status' => 'deleted', 'entity_type' => $entity_type, 'entity_id' => $entity_id, 'schema_type' => $schema_type];
    }

    private function entityExists(string $type, int $id): bool
    {
        switch ($type) {
            case 'product':
                $obj = new \Product($id);
                break;
            case 'category':
                $obj = new \Category($id);
                break;
            case 'cms':
                $obj = new \CMS($id);
                break;
            default:
                return false;
        }

        return Validate::isLoadedObject($obj);
    }

    private function clearEntityCache(): void
    {
        // Structured data is rendered in <head>, which may be cached. Flush the
        // template/page caches so the change is reflected on the next page load.
        if (method_exists('Tools', 'clearSmartyCache')) {
            \Tools::clearSmartyCache();
        }
        if (method_exists('Tools', 'clearXMLCache')) {
            \Tools::clearXMLCache();
        }
    }
}
