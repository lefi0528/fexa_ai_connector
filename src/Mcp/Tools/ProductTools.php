<?php

namespace PrestaShop\Module\FexaAiConnector\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use Context;
use Validate;
use PrestaShop\Module\FexaAiConnector\Helper\HtmlSanitizer;

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

        $idCategory = $idCategoryId ? (int)$idCategoryId : false;
        $products = \Product::getProducts($langId, $offset, $limit, 'id_product', 'ASC', $idCategory, $onlyActive);

        if (!is_array($products)) {
            return [];
        }

        return array_map(function($p) use ($langId, $context) {
            $images = \Image::getImages($langId, (int)$p['id_product']);
            $nbImages = is_array($images) ? count($images) : 0;
            $missingAlt = 0;
            if ($nbImages > 0) {
                foreach ($images as $img) {
                    if (empty($img['legend']) || $img['legend'] === $p['name']) {
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
                'price' => isset($p['price']) ? (float)$p['price'] : 0.0,
                'date_upd' => $p['date_upd'] ?? null
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

        $features = \Product::getFrontFeaturesStatic($idLang, $id_product);
        $formattedFeatures = [];
        foreach ($features as $f) {
            $formattedFeatures[] = [
                'name' => $f['name'],
                'value' => $f['value']
            ];
        }

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

        $category = new \Category($product->id_category_default, $idLang);
        $categoryName = Validate::isLoadedObject($category) ? $category->name : '';

        $images = \Image::getImages($idLang, $product->id);
        $formattedImages = [];
        if (is_array($images)) {
            foreach ($images as $img) {
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

        // --- Product schema enrichment: identifiers, stock, currency ---
        $quantity = (int) \StockAvailable::getQuantityAvailableByProduct((int) $product->id);
        $availableForOrder = (bool) $product->available_for_order;
        $availability = ($quantity > 0 && $availableForOrder)
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        $currencyIso = '';
        try {
            $defaultCurrency = \Currency::getDefaultCurrency();
            if ($defaultCurrency && isset($defaultCurrency->iso_code)) {
                $currencyIso = (string) $defaultCurrency->iso_code;
            }
        } catch (\Exception $e) {
            // leave empty — caller omits offers without a currency
        }

        $priceTaxIncl = (float) $product->price;
        try {
            $priceTaxIncl = (float) \Product::getPriceStatic((int) $product->id, true);
        } catch (\Exception $e) {
            // fall back to the base price already assigned
        }

        // Breadcrumb trail: category ancestry + the product itself as the last node.
        $breadcrumb = $this->buildBreadcrumbTrail((int) $product->id_category_default, (int) $idLang, $context);
        $breadcrumb[] = [
            'name' => is_string($product->name) ? $product->name : '',
            'url' => $context->link->getProductLink($product, null, null, null, $idLang),
        ];

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
            'nb_images' => count($formattedImages),
            'associations' => [
                'images' => $formattedImages
            ],
            'price_tax_excl' => (float)$product->price,
            'price_tax_incl' => $priceTaxIncl,
            'currency' => $currencyIso,
            'on_sale' => (bool)$product->on_sale,
            'ean13' => isset($product->ean13) ? (string) $product->ean13 : '',
            'isbn' => isset($product->isbn) ? (string) $product->isbn : '',
            'upc' => isset($product->upc) ? (string) $product->upc : '',
            'mpn' => isset($product->mpn) ? (string) $product->mpn : '',
            'condition' => isset($product->condition) ? (string) $product->condition : '',
            'quantity' => $quantity,
            'availability' => $availability,
            'available_for_order' => $availableForOrder,
            'breadcrumb' => $breadcrumb,
        ];
    }

    /**
     * Build a breadcrumb trail (Home → … → category) as [{name, url}] from a
     * category's ancestry. Best-effort: returns [] on any failure.
     */
    private function buildBreadcrumbTrail(int $idCategory, int $idLang, $context): array
    {
        $trail = [];
        try {
            $cat = new \Category($idCategory, $idLang);
            if (!Validate::isLoadedObject($cat)) {
                return $trail;
            }
            $parents = $cat->getParentsCategories($idLang); // current → … → root
            if (!is_array($parents)) {
                return $trail;
            }
            foreach (array_reverse($parents) as $p) {
                $idc = isset($p['id_category']) ? (int) $p['id_category'] : 0;
                if ($idc <= 1) {
                    continue; // skip the technical root category
                }
                $name = isset($p['name']) ? (string) $p['name'] : '';
                if ($name === '') {
                    continue;
                }
                $trail[] = [
                    'name' => $name,
                    'url' => $context->link->getCategoryLink($idc, isset($p['link_rewrite']) ? $p['link_rewrite'] : null, $idLang),
                ];
            }
        } catch (\Exception $e) {
            // best-effort
        }

        return $trail;
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
    ): array {
        $context = Context::getContext();
        $id_lang = $id_lang ?? (int)$context->language->id;

        if (!$id_lang) {
            $id_lang = (int)\Configuration::get('PS_LANG_DEFAULT');
        }

        $product = new \Product($id_product);

        if (!Validate::isLoadedObject($product)) {
            throw new \Exception("Product with ID $id_product not found.");
        }

        $fieldsUpdated = [];

        $updateField = function(&$fieldArray, $newValue, $fieldName) use ($id_lang, &$fieldsUpdated) {
            if ($newValue !== null) {
                if (!is_array($fieldArray)) {
                    $fieldArray = [$id_lang => $fieldArray];
                }
                $fieldArray[$id_lang] = $newValue;
                $fieldsUpdated[] = $fieldName;
            }
        };

        // Defense in depth: clean AI content before it reaches the shop.
        if ($description_short !== null) $description_short = HtmlSanitizer::richHtml($description_short);
        if ($description !== null) $description = HtmlSanitizer::richHtml($description);
        if ($meta_title !== null) $meta_title = HtmlSanitizer::meta($meta_title, 255);
        if ($meta_description !== null) $meta_description = HtmlSanitizer::meta($meta_description, 512);

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
