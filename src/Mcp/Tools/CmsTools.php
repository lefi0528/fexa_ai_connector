<?php

namespace PrestaShop\Module\FexaAiConnector\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Context;
use Validate;
use CMS;

class CmsTools
{
    #[McpTool(
        name: 'list_cms',
        description: 'List CMS pages. Returns ID, Meta Title, Link Rewrite, Active status.'
    )]
    #[Schema(
        properties: [
            'langId' => ['type' => 'integer', 'description' => 'Language ID'],
            'limit' => ['type' => 'integer', 'description' => 'Limit results (default 100)'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination (default 0)']
        ],
        required: []
    )]
    public function listCms(?int $langId = null, int $limit = 100, int $offset = 0): array
    {
        $context = Context::getContext();
        $idLang = $langId ?? $context->language->id;
        
        if (!$idLang) {
             $idLang = (int)\Configuration::get('PS_LANG_DEFAULT');
        }

        $cmsPages = \CMS::getCMSPages($idLang, null, true); // Active only

        // Apply pagination (limit and offset)
        if ($offset > 0 || count($cmsPages) > $limit) {
            $cmsPages = array_slice($cmsPages, $offset, $limit);
        }

        return array_map(function($c) use ($idLang, $context) {
            return [
                'id' => (int)$c['id_cms'],
                'name' => !empty($c['meta_title']) ? $c['meta_title'] : 'CMS #' . $c['id_cms'],
                'meta_title' => $c['meta_title'] ?? '',
                'link_rewrite' => $c['link_rewrite'] ?? '',
                'url' => $context->link->getCMSLink((int)$c['id_cms'], $c['link_rewrite'] ?? '', null, $idLang),
                'active' => isset($c['active']) ? (bool)$c['active'] : true, 
                'type' => 'cms'
            ];
        }, $cmsPages);
    }

    #[McpTool(
        name: 'get_cms_details',
        description: 'Get full details of a specific CMS page for SEO analysis.'
    )]
    #[Schema(
        properties: [
            'id_cms' => ['type' => 'integer', 'description' => 'CMS Page ID'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID']
        ],
        required: ['id_cms']
    )]
    public function getCmsDetails(int $id_cms, ?int $id_lang = null): array
    {
        $context = Context::getContext();
        $idLang = $id_lang ?? $context->language->id;

        $cms = new \CMS($id_cms, $idLang);

        if (!Validate::isLoadedObject($cms)) {
            throw new \Exception("CMS page with ID $id_cms not found.");
        }

        return [
            'id' => $cms->id,
            'name' => $cms->meta_title,
            'content' => $cms->content,
            'meta_title' => $cms->meta_title,
            'meta_description' => $cms->meta_description,
            'link_rewrite' => $cms->link_rewrite,
            'url' => $context->link->getCMSLink($cms, null, null, $idLang),
            'active' => $cms->active,
            'indexation' => $cms->indexation
        ];
    }

    #[McpTool(
        name: 'update_cms_seo',
        description: 'Update SEO fields and content of a CMS page.'
    )]
    #[Schema(
        properties: [
            'id_cms' => ['type' => 'integer', 'description' => 'CMS ID'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID'],
            'content' => ['type' => 'string', 'description' => 'Main Content (HTML)'],
            'meta_title' => ['type' => 'string', 'description' => 'Meta Title'],
            'meta_description' => ['type' => 'string', 'description' => 'Meta Description']
        ],
        required: ['id_cms']
    )]
    public function updateCmsSeo(
        int $id_cms, 
        ?int $id_lang = null, 
        ?string $content = null, 
        ?string $meta_title = null, 
        ?string $meta_description = null
    ): array
    {
        $context = Context::getContext();
        $id_lang = $id_lang ?? (int)$context->language->id;

        if (!$id_lang) {
            $id_lang = (int)\Configuration::get('PS_LANG_DEFAULT');
        }

        $cms = new \CMS($id_cms);

        if (!Validate::isLoadedObject($cms)) {
            throw new \Exception("CMS with ID $id_cms not found.");
        }

        $fieldsUpdated = [];

        // Helper to update multi-lang field
        $updateField = function(&$fieldArray, $newValue, $fieldName) use ($id_lang, &$fieldsUpdated) {
            if ($newValue !== null) {
                if (!is_array($fieldArray)) {
                    $fieldArray = [$id_lang => $fieldArray]; 
                }
                $fieldArray[$id_lang] = $newValue;
                $fieldsUpdated[] = $fieldName;
            }
        };

        $updateField($cms->content, $content, 'content');
        $updateField($cms->meta_title, $meta_title, 'meta_title');
        $updateField($cms->meta_description, $meta_description, 'meta_description');

        if (empty($fieldsUpdated)) {
            return ['status' => 'no_changes'];
        }

        if (!$cms->save()) {
            throw new \Exception("Failed to save CMS ID $id_cms");
        }

        return [
            'status' => 'success',
            'cms_id' => $id_cms,
            'lang_id' => $id_lang,
            'updated_fields' => $fieldsUpdated
        ];
    }
}
