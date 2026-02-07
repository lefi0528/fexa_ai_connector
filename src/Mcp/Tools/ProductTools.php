<?php

namespace PrestaShop\Module\FexaAiConnector\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use Context;
use Validate;

class ProductTools
{
    #[McpTool(
        name: 'list_products',
        description: 'List products from the shop with pagination. Returns basic details (ID, Reference, Name, Active status).'
    )]
    #[Schema(
        properties: [
            'langId' => ['type' => 'integer', 'description' => 'Language ID (default context language if not provided)'],
            'limit' => ['type' => 'integer', 'description' => 'Number of products to return (default 50)'],
            'offset' => ['type' => 'integer', 'description' => 'Offset for pagination (default 0)'],
            'onlyActive' => ['type' => 'boolean', 'description' => 'Filter only active products (default true)'],
            'idCategoryId' => ['type' => 'integer', 'description' => 'Filter by category ID (optional)']
        ],
        required: []
    )]
    public function listProducts(?int $langId = null, int $limit = 50, int $offset = 0, bool $onlyActive = true, ?int $idCategoryId = null): array
    {
        $context = Context::getContext();
        
        // Safety check for Context
        if (!$context) {
            // Try to recover or default
             throw new \Exception('PrestaShop Context not initialized.');
        }

        // Safety check for Language
        if (!$langId) {
            if (isset($context->language) && isset($context->language->id)) {
                $langId = (int)$context->language->id;
            } else {
                 // Fallback to Configuration default language
                 $langId = (int)\Configuration::get('PS_LANG_DEFAULT');
            }
        }
        
        if (empty($langId)) {
             throw new \Exception('Could not determine Language ID.');
        }

        $idCategory = $idCategoryId ? (int)$idCategoryId : false;
        error_log("[MCP/ProductTools] listProducts called: langId=$langId, limit=$limit, offset=$offset, idCategory=" . var_export($idCategory, true) . ", onlyActive=" . var_export($onlyActive, true));
        $products = \Product::getProducts($langId, $offset, $limit, 'id_product', 'ASC', $idCategory, $onlyActive);
        
        if (!is_array($products)) {
             return [];
        }

        // Sanitize output to return cleaner JSON
        return array_map(function($p) use ($langId, $context) {
            $images = \Image::getImages($langId, (int)$p['id_product']);
            $nbImages = is_array($images) ? count($images) : 0;
            $missingAlt = 0;
            if ($nbImages > 0) {
                 foreach ($images as $img) {
                     if (empty($img['legend']) || $img['legend'] === $p['name']) { // Empty or just product name is weak
                         $missingAlt++;
                     }
                 }
            }

            return [
                'id' => (int)$p['id_product'],
                'name' => !empty($p['name']) ? $p['name'] : 'Product #' . $p['id_product'],
                'reference' => isset($p['reference']) ? $p['reference'] : '',
                'active' => (bool)$p['active'],
                'category_default' => $p['id_category_default'],
                'nb_images' => $nbImages,
                'missing_alt' => $missingAlt,
                'description' => $p['description'] ?? '',
                'description_short' => $p['description_short'] ?? '',
                'meta_title' => $p['meta_title'] ?? '',
                'link_rewrite' => $p['link_rewrite'] ?? '',
                'url' => $context->link->getProductLink((int)$p['id_product'], $p['link_rewrite'] ?? '', $p['category_default'], null, $langId),
                'manufacturer_name' => $p['manufacturer_name'] ?? '',
                'price' => isset($p['price']) ? (float)$p['price'] : 0.0
            ];
        }, $products);
    }

    #[McpTool(
        name: 'get_product_details',
        description: 'Get full details of a specific product (descriptions, meta, features) for SEO analysis.'
    )]
    #[Schema(
        properties: [
            'id_product' => ['type' => 'integer', 'description' => 'Product ID'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID']
        ],
        required: ['id_product']
    )]
    public function getProductDetails(int $id_product, ?int $id_lang = null): array
    {
        $context = Context::getContext();
        $idLang = $id_lang ?? (int)$context->language->id;
        
        $product = new \Product($id_product, false, $idLang);
        
        if (!Validate::isLoadedObject($product)) {
            throw new \Exception("Product with ID $id_product not found.");
        }

        // 1. Fetch Features
        $features = \Product::getFrontFeaturesStatic($idLang, $id_product);
        $formattedFeatures = [];
        foreach ($features as $f) {
            $formattedFeatures[] = [
                'name' => $f['name'],
                'value' => $f['value']
            ];
        }

        // 2. Fetch Combinations (Attributes)
        $attributes = $product->getAttributesGroups($idLang);
        $formattedCombinations = [];
        foreach ($attributes as $a) {
            $combId = (int)$a['id_product_attribute'];
            if (!isset($formattedCombinations[$combId])) {
                $formattedCombinations[$combId] = [
                    'id' => $combId,
                    'reference' => $a['reference'],
                    'attributes' => []
                ];
            }
            $formattedCombinations[$combId]['attributes'][] = [
                'group' => $a['group_name'],
                'name' => $a['attribute_name']
            ];
        }

        // 3. Fetch Category Name
        $category = new \Category($product->id_category_default, $idLang);
        $categoryName = Validate::isLoadedObject($category) ? $category->name : '';

        // 4. Fetch Images (Missed in V1)
        $images = \Image::getImages($idLang, $product->id);
        $formattedImages = [];
        if (is_array($images)) {
            foreach ($images as $img) {
                // Determine full URL if possible, or just IDs
                // Context link helper is best
                $imageUrl = $context->link->getImageLink($product->link_rewrite[$idLang] ?? $product->name[$idLang], $img['id_image']);
                
                $formattedImages[] = [
                    'id' => $img['id_image'],
                    'cover' => (bool)$img['cover'],
                    'legend' => $img['legend'],
                    'position' => $img['position'],
                    'url' => strpos($imageUrl, 'http') === 0 ? $imageUrl : 'http://' . $imageUrl
                ];
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description_short' => $product->description_short,
            'description' => $product->description,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'link_rewrite' => $product->link_rewrite,
            'url' => $context->link->getProductLink($product, null, null, null, $idLang),
            'reference' => $product->reference,
            'active' => (bool)$product->active,
            'manufacturer_name' => $product->manufacturer_name ?: '',
            'category_name' => $categoryName,
            'features' => $formattedFeatures,
            'combinations' => array_values($formattedCombinations),
            'nb_images' => count($formattedImages), // Helper count
            'associations' => [
                 'images' => $formattedImages
            ],
            'price_tax_excl' => (float)$product->price,
            'on_sale' => (bool)$product->on_sale,
        ];
    }

    #[McpTool(
        name: 'update_product_seo',
        description: 'Update SEO fields and descriptions of a product. Supports partial updates (pass null to keep existing value).'
    )]
    #[Schema(
        properties: [
            'id_product' => ['type' => 'integer', 'description' => 'Product ID'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID to update'],
            'description_short' => ['type' => 'string', 'description' => 'New short description (HTML allowed)'],
            'description' => ['type' => 'string', 'description' => 'New long description (HTML allowed)'],
            'meta_title' => ['type' => 'string', 'description' => 'New Meta Title'],
            'meta_description' => ['type' => 'string', 'description' => 'New Meta Description'],
        ],
        required: ['id_product']
    )]
    public function updateProductSeo(
        int $id_product, 
        ?int $id_lang = null, 
        ?string $description_short = null, 
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

        // Load product
        $product = new \Product($id_product);
        
        if (!Validate::isLoadedObject($product)) {
            throw new \Exception("Product with ID $id_product not found.");
        }

        $fieldsUpdated = [];

        // Helper to update multi-lang field for a specific ID
        // PrestaShop stores multi-lang fields as arrays [id_lang => value]
        
        $updateField = function(&$fieldArray, $newValue, $fieldName) use ($id_lang, &$fieldsUpdated) {
            if ($newValue !== null) {
                // If it's not an array yet (single lang store context), force it to array
                if (!is_array($fieldArray)) {
                    $fieldArray = [$id_lang => $fieldArray]; 
                }
                $fieldArray[$id_lang] = $newValue;
                $fieldsUpdated[] = $fieldName;
            }
        };

        $updateField($product->description_short, $description_short, 'description_short');
        $updateField($product->description, $description, 'description');
        $updateField($product->meta_title, $meta_title, 'meta_title');
        $updateField($product->meta_description, $meta_description, 'meta_description');

        if (empty($fieldsUpdated)) {
            return ['status' => 'no_changes', 'updated_fields' => []];
        }

        if (!$product->save()) {
            throw new \Exception("Failed to save product ID $id_product");
        }

        return [
            'status' => 'success',
            'product_id' => $id_product,
            'lang_id' => $id_lang,
            'updated_fields' => $fieldsUpdated
        ];
    }

    #[McpTool(
        name: 'update_image_alt',
        description: 'Update the ALT text (legend) of a specific image for a given language.'
    )]
    #[Schema(
        properties: [
            'id_image' => ['type' => 'integer', 'description' => 'Image ID'],
            'legend' => ['type' => 'string', 'description' => 'New ALT text (legend)'],
            'id_lang' => ['type' => 'integer', 'description' => 'Language ID (optional, defaults to context language)']
        ],
        required: ['id_image', 'legend']
    )]
    public function updateImageAlt(int $id_image, string $legend, ?int $id_lang = null): array
    {
        $context = Context::getContext();
        $id_lang = $id_lang ?? (int)$context->language->id;

        if (!$id_lang) {
            $id_lang = (int)\Configuration::get('PS_LANG_DEFAULT');
        }

        $image = new \Image($id_image);
        if (!Validate::isLoadedObject($image)) {
            throw new \Exception("Image with ID $id_image not found.");
        }

        // Multi-lang legend update
        if (!is_array($image->legend)) {
            $image->legend = [$id_lang => $legend];
        } else {
            $image->legend[$id_lang] = $legend;
        }

        if (!$image->save()) {
            throw new \Exception("Failed to save image legend for ID $id_image");
        }

        return [
            'status' => 'success',
            'id_image' => $id_image,
            'id_lang' => $id_lang,
            'legend' => $legend
        ];
    }
}
