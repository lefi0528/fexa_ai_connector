<?php

namespace PrestaShop\Module\FexaAiConnector\Legacy\Tools;

use Product;
use Language;
use Db;
use Configuration;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductTool
{
    public static function listProducts($params)
    {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $sql = 'SELECT p.id_product, pl.name, p.reference, p.price, pl.description_short 
                FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . $id_lang . ')
                WHERE p.active = 1
                ORDER BY p.id_product DESC
                LIMIT ' . $offset . ', ' . $limit;

        $products = Db::getInstance()->executeS($sql);

        return $products;
    }

    public static function getProduct($params)
    {
        if (!isset($params['id'])) {
            throw new \Exception('Product ID is required');
        }

        $id_product = (int)$params['id'];
        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $product = new Product($id_product, true, $id_lang);

        if (!Validate::isLoadedObject($product)) {
            throw new \Exception('Product not found');
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'description_short' => $product->description_short,
            'price' => $product->price,
            'reference' => $product->reference,
            'link_rewrite' => $product->link_rewrite,
            'active' => $product->active
        ];
    }

    public static function updateProduct($params)
    {
        if (!isset($params['id'])) {
            throw new \Exception('Product ID is required');
        }

        $id_product = (int)$params['id'];
        $product = new Product($id_product);

        if (!Validate::isLoadedObject($product)) {
            throw new \Exception('Product not found');
        }

        $languages = Language::getLanguages(false);

        if (isset($params['name'])) {
            foreach ($languages as $lang) {
                $product->name[$lang['id_lang']] = $params['name']; 
            }
        }

        if (isset($params['description'])) {
            foreach ($languages as $lang) {
                $product->description[$lang['id_lang']] = $params['description']; 
            }
        }

        if (isset($params['description_short'])) {
             foreach ($languages as $lang) {
                $product->description_short[$lang['id_lang']] = $params['description_short']; 
            }
        }

        if (!$product->update()) {
             throw new \Exception('Failed to update product');
        }

        return ['success' => true, 'id' => $product->id];
    }
}
