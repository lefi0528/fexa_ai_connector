<?php

namespace PrestaShop\Module\FexaAiConnector\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Context;
use Validate;
use Category;

class CategoryTools
{
    #[McpTool(
        name: 'list_categories',
        description: 'List categories tree or flat list. Returns ID, Name, Active status.'
    )]
    #[Schema(
        properties: [
            'langId' => ['type' => 'integer', 'description' => 'Language ID'],
            'rootCategoryId' => ['type' => 'integer', 'description' => 'Root category ID to start from (default Home Category)'],
            'limit' => ['type' => 'integer', 'description' => 'Limit results (default 100)'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination (default 0)']
        ],
        required: []
    )]
    public function listCategories(?int $langId = null, ?int $rootCategoryId = null, int $limit = 100, int $offset = 0): array
    {
        $context = Context::getContext();
        
        // Safety checks
        if (!$context) {
             throw new \Exception('PrestaShop Context not initialized.');
        }

        if (!$langId) {
             if (isset($context->language) && isset($context->language->id)) {
                 $langId = (int)$context->language->id;
             } else {
                 $langId = (int)\Configuration::get('PS_LANG_DEFAULT');
             }
        }
        
        if (empty($langId)) {
             throw new \Exception('Could not determine Language ID.');
        }

        $idRoot = $rootCategoryId ?? $context->shop->getCategory();
        
        if (!$idRoot) {
            $idRoot = (int)\Configuration::get('PS_ROOT_CATEGORY');
        }
    
        $categories = \Category::getChildren($idRoot, $langId, true); // Active only
    
        if (!is_array($categories)) {
            $categories = [];
        }
    
        // Apply pagination (limit and offset)
        if ($offset > 0 || count($categories) > $limit) {
            $categories = array_slice($categories, $offset, $limit);
        }

        return array_map(function($c) use ($langId, $context) {
            return [
                'id' => (int)$c['id_category'],
                'name' => !empty($c['name']) ? $c['name'] : 'Category #' . $c['id_category'],
                'description' => isset($c['description']) ? strip_tags($c['description']) : '', // Preview only
                'active' => isset($c['active']) ? (bool)$c['active'] : true, 
                'link_rewrite' => $c['link_rewrite'] ?? '',
                'url' => $context->link->getCategoryLink((int)$c['id_category'], $c['link_rewrite'] ?? '', $langId),
            ];
        }, $categories);
    }

    #[McpTool(
        name: 'get_category_details',
        description: 'Get full details of a specific category (descriptions, meta) for SEO analysis.'
    )]
    #[Schema(
        properties: [
            'id_category' => ['type' => 'integer', 'description' => 'Category ID'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID']
        ],
        required: ['id_category']
    )]
    public function getCategoryDetails(int $id_category, ?int $id_lang = null): array
    {
        $context = Context::getContext();
        $idLang = $id_lang ?? $context->language->id;

        $category = new \Category($id_category, $idLang);

        if (!Validate::isLoadedObject($category)) {
            throw new \Exception("Category with ID $id_category not found.");
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
            'link_rewrite' => $category->link_rewrite,
            'url' => $context->link->getCategoryLink($category, null, $idLang),
            'active' => $category->active,
            'level_depth' => $category->level_depth,
            'id_parent' => $category->id_parent,
            'nb_products' => (int)$category->getProducts($idLang, 1, 1, null, null, true), // Count only
            'has_image' => file_exists(_PS_CAT_IMG_DIR_ . (int)$category->id . '.jpg')
        ];
    }

    #[McpTool(
        name: 'update_category_seo',
        description: 'Update SEO fields and description of a category. Supports partial updates.'
    )]
    #[Schema(
        properties: [
            'id_category' => ['type' => 'integer', 'description' => 'Category ID'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID to update'],
            'description' => ['type' => 'string', 'description' => 'New description (HTML allowed)'],
            'meta_title' => ['type' => 'string', 'description' => 'New Meta Title'],
            'meta_description' => ['type' => 'string', 'description' => 'New Meta Description'],
        ],
        required: ['id_category']
    )]
    public function updateCategorySeo(
        int $id_category, 
        ?int $id_lang = null, 
        ?string $description = null, 
        ?string $meta_title = null, 
        ?string $meta_description = null
    ): array
    {
        $context = Context::getContext();
        $id_lang = $id_lang ?? (int)$context->language->id;

        if (!$id_lang) {
            $id_lang = (int)\Configuration::get('PS_LANG_DEFAULT');
        }

        // Load category
        $category = new \Category($id_category);

        if (!Validate::isLoadedObject($category)) {
            throw new \Exception("Category with ID $id_category not found.");
        }

        $fieldsUpdated = [];

        // Update helper (same logic as product)
        $updateField = function(&$fieldArray, $newValue, $fieldName) use ($id_lang, &$fieldsUpdated) {
            if ($newValue !== null) {
                if (!is_array($fieldArray)) {
                    $fieldArray = [$id_lang => $fieldArray]; 
                }
                $fieldArray[$id_lang] = $newValue;
                $fieldsUpdated[] = $fieldName;
            }
        };

        $updateField($category->description, $description, 'description');
        $updateField($category->meta_title, $meta_title, 'meta_title');
        $updateField($category->meta_description, $meta_description, 'meta_description');

        if (empty($fieldsUpdated)) {
            return ['status' => 'no_changes', 'updated_fields' => []];
        }

        if (!$category->save()) {
            throw new \Exception("Failed to save category ID $id_category");
        }

        return [
            'status' => 'success',
            'category_id' => $id_category,
            'lang_id' => $id_lang,
            'updated_fields' => $fieldsUpdated
        ];
    }

}
