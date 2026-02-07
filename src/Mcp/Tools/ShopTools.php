<?php

namespace PrestaShop\Module\FexaAiConnector\Mcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Context;
use Language;

class ShopTools
{
    #[McpTool(
        name: 'list_languages',
        description: 'Get list of active languages in the shop.'
    )]
    #[Schema(
        properties: [],
        required: []
    )]
    public function listLanguages(): array
    {
        $languages = Language::getLanguages(true, Context::getContext()->shop->id);
        
        return array_map(function($l) {
            return [
                'id' => (int)$l['id_lang'],
                'name' => $l['name'],
                'iso_code' => $l['iso_code'],
                'language_code' => $l['language_code'],
                'is_default' => (int)$l['id_lang'] === (int)\Configuration::get('PS_LANG_DEFAULT')
            ];
        }, $languages);
    }
}
