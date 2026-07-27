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
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fexa_ai_connector extends Module
{
    private $serviceContainer;

    public $adminControllers;
    public $version;
    public $multistoreCompatibility;
    public $emailSupport;
    public $termsOfServiceUrl;

    public function __construct()
    {
        $this->name = 'fexa_ai_connector';
        $this->author = 'Fexa AI';
        $this->tab = 'seo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->version = '3.7.1';
        // PrestaShop Addons product key. Paste the key generated on your Addons product
        // page here before uploading the zip to the Marketplace; it drives back-office
        // update notifications for merchants who install from Addons.
        $this->module_key = '';

        parent::__construct();

        $this->displayName = $this->l('Fexa AI Connector');
        $this->description = $this->l('Connect your store with Fexa AI services.');
        $this->confirmUninstall = $this->l('Do you really want to uninstall Fexa AI Connector?');
        // Requires PHP 8.1+ (php-mcp/server) — which in practice means PrestaShop
        // 8.1+ / 9.x. Older shops (1.7.x, PS 8.0 on PHP 7.x) cannot meet the PHP
        // floor, so we don't advertise compatibility we can't honour.
        $this->ps_versions_compliancy = ['min' => '8.1.0', 'max' => '9.99.99'];
        $this->adminControllers = [];

        // Defensive: the module constructor must NEVER throw. On PrestaShop 9
        // (Symfony 6) building the service container can fail (e.g. a module-lib
        // version mismatch). If it throws here, Module::getInstance() returns null
        // and PS9 core then fatals on `method_exists(null, 'getContent')`
        // (ModuleController::configureModuleAction) — bricking the whole
        // back-office. We degrade gracefully instead: the container is only used
        // lazily via getService(), which already handles a null container.
        if (null === $this->serviceContainer) {
            try {
                $this->serviceContainer = new ServiceContainer(
                    (string) $this->name,
                    $this->getLocalPath()
                );
            } catch (Throwable $e) {
                $this->serviceContainer = null;
                error_log('[fexa_ai_connector] ServiceContainer init failed (PS9?): ' . $e->getMessage());
            }
        }
    }

    public function install(): bool
    {
        return $this->installDatabaseTables()
            && parent::install()
            && $this->registerHook($this->getHooksList())
            && Configuration::updateValue('FEXA_AI_SERVER_STARTED', true)
            && Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false)
            && Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true)
            && Configuration::updateValue('FEXA_AI_SERVER_LOGS_ENABLED', false)
            // Per-schema injection toggles. Safe defaults: FAQPage ON (themes never emit it),
            // Product & BreadcrumbList OFF (PrestaShop themes already emit these natively —
            // emitting them again would create duplicate JSON-LD nodes that can hurt SEO).
            && Configuration::updateValue('FEXA_AI_EMIT_FAQPAGE', '1')
            && Configuration::updateValue('FEXA_AI_EMIT_PRODUCT', '0')
            && Configuration::updateValue('FEXA_AI_EMIT_BREADCRUMB', '0')
            && $this->ensureApiKey();
    }

    public function uninstall(): bool
    {
        return $this->uninstallDatabaseTables()
            && parent::uninstall()
            && Configuration::deleteByName('FEXA_AI_SERVER_STARTED')
            && Configuration::deleteByName('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE')
            && Configuration::deleteByName('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER')
            && Configuration::deleteByName('FEXA_AI_SERVER_LOGS_ENABLED')
            && Configuration::deleteByName('FEXA_AI_EMIT_FAQPAGE')
            && Configuration::deleteByName('FEXA_AI_EMIT_PRODUCT')
            && Configuration::deleteByName('FEXA_AI_EMIT_BREADCRUMB')
            && Configuration::deleteByName('FEXA_AI_API_KEY');
    }

    public function upgrade($version): bool
    {
        Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);

        return true;
    }

    public function isMcpCompliant(): bool
    {
        return true;
    }

    public function getMultistoreCompatibility(): int
    {
        return (int) true;
    }

    const FEXA_APP_BASE_URL = 'https://fexaai.com';

    public function getContent()
    {
        // Rendered as legacy module content (no Symfony admin controller): works
        // identically on PrestaShop 8 / 9. PS 9 removed the controller base
        // class infrastructure used previously, which threw "has no container set".
        // Handle the structured-data toggle form (saved before rendering the page).
        $schemaSaved = false;
        if (Tools::isSubmit('submitFexaSchemas')) {
            Configuration::updateValue('FEXA_AI_EMIT_FAQPAGE', Tools::getValue('emit_faqpage') ? '1' : '0');
            Configuration::updateValue('FEXA_AI_EMIT_PRODUCT', Tools::getValue('emit_product') ? '1' : '0');
            Configuration::updateValue('FEXA_AI_EMIT_BREADCRUMB', Tools::getValue('emit_breadcrumb') ? '1' : '0');
            $schemaSaved = true;
        }

        // One-click assisted connection: send this shop's URL + API key to Fexa AI, which returns a
        // short verification code and a claim link the merchant follows to finish binding the shop.
        $fexaConnect = null;
        if (Tools::isSubmit('submitFexaConnect')) {
            $fexaConnect = $this->pairWithFexa();
        }

        $this->context->smarty->assign([
            'fexa_intro' => $this->l('Optimisez automatiquement votre boutique pour le SEO ET la recherche vocale (Google Assistant, Alexa, Siri) grâce à l\'IA : descriptions, méta, balises ALT, données structurées et fichier /llms.txt prêts pour les moteurs de réponse.'),
            'fexa_access' => $this->l('Accéder à Fexa AI'),
            'fexa_feat_title' => $this->l('Ce que Fexa AI optimise pour vous'),
            'fexa_f1t' => $this->l('Recherche vocale (AEO)'),
            'fexa_f1d' => $this->l('Balisage Speakable + FAQ pour être lu à voix haute par les assistants vocaux.'),
            'fexa_f2t' => $this->l('Données structurées'),
            'fexa_f2d' => $this->l('JSON-LD Produit, FAQ et fil d\'Ariane injectés automatiquement (rich snippets Google).'),
            'fexa_f3t' => $this->l('SEO automatisé par IA'),
            'fexa_f3d' => $this->l('Titres, méta-descriptions, descriptions et balises ALT réécrits et optimisés.'),
            'fexa_f4t' => $this->l('Moteurs de réponse IA'),
            'fexa_f4d' => $this->l('Contenu prêt pour ChatGPT, Google SGE et les réponses générées par IA.'),
            'fexa_f5t' => $this->l('Fichier /llms.txt (nouveau)'),
            'fexa_f5d' => $this->l('Une carte de votre boutique lisible par les IA, générée depuis votre catalogue et servie automatiquement à la racine (/llms.txt).'),
            'fexa_connect_title' => $this->l('Connectez votre boutique en 1 clic'),
            'fexa_connect_intro' => $this->l('Cliquez ci-dessous : Fexa AI vous renverra un code de vérification à saisir sur son site pour finaliser. Aucune clé à copier-coller.'),
            'fexa_connect_btn' => $this->l('Connecter à Fexa AI'),
            'fexa_connect_code_help' => $this->l('Ouvrez Fexa AI avec le bouton ci-dessous, puis saisissez ce code de vérification pour finaliser la connexion :'),
            'fexa_connect_finish' => $this->l('Ouvrir Fexa AI pour finaliser'),
            'fexa_connect_err' => $this->l('La connexion automatique n\'est pas disponible pour le moment. Utilisez votre clé API ci-dessous pour connecter votre boutique manuellement.'),
            'fexa_connect_manual_hint' => $this->l('Connexion manuelle (avancé)'),
            'fexa_connect_code' => (isset($fexaConnect['code']) ? (string) $fexaConnect['code'] : ''),
            'fexa_connect_url' => (isset($fexaConnect['claimUrl']) ? (string) $fexaConnect['claimUrl'] : ''),
            'fexa_connect_error' => (isset($fexaConnect['error']) && $fexaConnect['error']),
            'fexa_key_title' => $this->l('Votre clé API'),
            'fexa_key_help' => $this->l('Copiez cette clé et collez-la dans votre tableau de bord Fexa AI pour connecter votre boutique.'),
            'fexa_copy' => $this->l('Copier la clé'),
            'fexa_api_key' => (string) Configuration::get('FEXA_AI_API_KEY'),
            'fexa_schema_title' => $this->l('Données structurées (JSON-LD)'),
            'fexa_schema_intro' => $this->l('Choisissez les schémas que Fexa injecte. Astuce : la plupart des thèmes PrestaShop émettent déjà Product et Fil d\'Ariane — les laisser désactivés évite les doublons qui peuvent nuire au SEO. FAQ est unique à Fexa, gardez-le activé.'),
            'fexa_lbl_faq' => $this->l('FAQ (FAQPage) — recommandé, unique à Fexa'),
            'fexa_lbl_product' => $this->l('Produit (Product) — à activer seulement si votre thème ne le fournit pas'),
            'fexa_lbl_breadcrumb' => $this->l('Fil d\'Ariane (BreadcrumbList) — à activer seulement si votre thème ne le fournit pas'),
            'fexa_save_lbl' => $this->l('Enregistrer'),
            'fexa_saved_lbl' => $this->l('Réglages enregistrés.'),
            'fexa_schema_saved' => $schemaSaved,
            'fexa_emit_faq' => $this->isSchemaEnabled('FAQPage'),
            'fexa_emit_product' => $this->isSchemaEnabled('Product'),
            'fexa_emit_breadcrumb' => $this->isSchemaEnabled('BreadcrumbList'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * Start a one-click pairing with Fexa AI. Sends this shop's URL + API key over a verified TLS
     * connection to the public pairing endpoint and returns ['code' => ..., 'claimUrl' => ...] on
     * success, or ['error' => true] on any failure (the merchant then falls back to the manual
     * API-key card). The API key never appears in a URL — only in the request body over HTTPS.
     */
    private function pairWithFexa()
    {
        $this->ensureApiKey();
        $apiKey = (string) Configuration::get('FEXA_AI_API_KEY');
        $shopUrl = (string) Tools::getShopDomainSsl(true);
        if ($apiKey === '' || $shopUrl === '') {
            return ['error' => true];
        }

        $payload = json_encode([
            'shopUrl' => $shopUrl,
            'apiKey' => $apiKey,
            'psVersion' => _PS_VERSION_,
            'moduleVersion' => $this->version,
        ]);

        $ch = curl_init(self::FEXA_APP_BASE_URL . '/api/connect/pair/init');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false || $status !== 200) {
            return ['error' => true];
        }
        $data = json_decode($resp, true);
        if (!is_array($data) || empty($data['ok']) || empty($data['code']) || empty($data['claimUrl'])) {
            return ['error' => true];
        }
        return ['code' => (string) $data['code'], 'claimUrl' => (string) $data['claimUrl']];
    }

    public static function getConfig(): array
    {
        return [];
    }

    public function ensureApiKey(): bool
    {
        if (!Configuration::get('FEXA_AI_API_KEY')) {
            return Configuration::updateValue('FEXA_AI_API_KEY', bin2hex(random_bytes(32)));
        }

        return true;
    }

    public function installDatabaseTables(): bool
    {
        $dbInstallFile = __DIR__ . '/sql/install.sql';

        if (!file_exists($dbInstallFile)) {
            return false;
        }

        $sql = (string) Tools::file_get_contents($dbInstallFile);

        if (empty($sql)) {
            return false;
        }

        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);
        $sql = preg_split("/;\s*[\r\n]*/", $sql);

        $success = true;

        if (!empty($sql)) {
            foreach ($sql as $query) {
                $query = preg_replace('/--.*$/m', '', $query);
                $query = preg_replace('/#.*$/m', '', $query);
                $query = preg_replace('/\/\*.*?\*\//s', '', $query);
                $query = trim($query);
                if (empty($query)) {
                    continue;
                }
                if (!Db::getInstance()->execute($query)) {
                    $success = false;
                    break;
                }
            }
        }

        return $success;
    }

    public function uninstallDatabaseTables(): bool
    {
        $dbUninstallFile = __DIR__ . '/sql/uninstall.sql';

        if (!file_exists($dbUninstallFile)) {
            return false;
        }

        $sql = (string) Tools::file_get_contents($dbUninstallFile);

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]*/", $sql);

        $success = true;

        if (!empty($sql)) {
            foreach ($sql as $query) {
                $query = trim($query);
                if (empty($query)) {
                    continue;
                }
                if (!Db::getInstance()->execute($query)) {
                    $success = false;
                    break;
                }
            }
        }

        return $success;
    }

    public function tableExist(string $tableName): bool
    {
        $table = _DB_PREFIX_ . $tableName;
        $query = 'SHOW TABLES LIKE \'' . $table . '\'';

        return (bool) Db::getInstance()->executeS($query);
    }

    public function getService($serviceName)
    {
        try {
            return $this->serviceContainer->getService($serviceName);
        } catch (Throwable $e) {
            // Don't fail hard, but leave a trace: a silent null here surfaces downstream
            // as a confusing "service is not available" 500 with no cause in the logs.
            error_log('[fexa_ai_connector] getService("' . $serviceName . '") failed: '
                . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            $container = SymfonyContainer::getInstance();
            if (null !== $container) {
                $shortId = 'fexa_ai_connector.' . $serviceName;
                if ($container->has($shortId)) {
                    return $container->get($shortId);
                }
                if ($container->has($serviceName)) {
                    return $container->get($serviceName);
                }
            }
        }

        return null;
    }

    public function getHooksList(): array
    {
        return [
            'moduleRoutes',
            'displayHeader',
        ];
    }

    public function hookModuleRoutes(): array
    {
        return [
            'fexa_ai_connector-mcp-server' => [
                'controller' => 'McpServer',
                'rule' => 'mcp',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ],
            ],
            // Serve the shop's machine-readable AEO map at the domain root.
            'fexa_ai_connector-llms-txt' => [
                'controller' => 'llms',
                'rule' => 'llms.txt',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ],
            ],
        ];
    }

    /**
     * Inject AI-generated structured data (JSON-LD) into the <head> of the
     * current product / category / CMS page. The payload is stored by the
     * set_structured_data MCP tool — never in the (sanitised) description field.
     */
    public function hookDisplayHeader($params): string
    {
        $controller = isset($this->context->controller->php_self) ? $this->context->controller->php_self : '';
        $idParam = [
            'product' => 'id_product',
            'category' => 'id_category',
            'cms' => 'id_cms',
        ];

        if (!isset($idParam[$controller])) {
            return '';
        }

        $entityId = (int) Tools::getValue($idParam[$controller]);
        if ($entityId <= 0) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $rows = Db::getInstance()->executeS(
            'SELECT `jsonld`, `schema_type` FROM `' . _DB_PREFIX_ . 'fexa_ai_structured_data` '
            . "WHERE entity_type='" . pSQL($controller) . "' AND entity_id=" . $entityId
            . ' AND is_active=1 AND (id_lang=' . $idLang . ' OR id_lang=0) '
            . 'AND (id_shop=' . $idShop . ' OR id_shop=0)'
        );

        if (!is_array($rows) || 0 === count($rows)) {
            return '';
        }

        // JSON-LD is rendered through a Smarty template (so the module ships no HTML in PHP).
        // The payload is passed through the `fexajsonld` modifier (views/smarty-plugins/), which
        // neutralises the only breakout vector — a premature closing sequence — while keeping the
        // JSON valid. HTML-escaping is NOT used: a ld+json script's content is raw text the browser
        // never HTML-decodes, so entity-encoding would corrupt it for crawlers. The same sanitising
        // is also applied here in PHP as a defence-in-depth safety net (the modifier is idempotent).
        $blocks = [];
        foreach ($rows as $row) {
            // Skip schema types the merchant disabled (e.g. Product / BreadcrumbList already
            // emitted natively by the theme) to avoid duplicate JSON-LD nodes that hurt SEO.
            if (!$this->isSchemaEnabled((string) $row['schema_type'])) {
                continue;
            }
            $blocks[] = str_replace('</', '<\\/', (string) $row['jsonld']);
        }

        if (0 === count($blocks)) {
            return '';
        }

        try {
            $this->context->smarty->addPluginsDir($this->local_path . 'views/smarty-plugins/');
            $this->context->smarty->assign('fexa_jsonld_blocks', $blocks);

            return $this->display(__FILE__, 'views/templates/hook/structured_data.tpl');
        } catch (Throwable $e) {
            // Fail safe: never break the page head if template/modifier rendering fails.
            return '';
        }
    }

    /**
     * Whether a given JSON-LD schema_type should be injected, per the module config.
     * Safe defaults when never configured (fresh install or pre-toggle upgrade):
     * FAQPage ON (themes never provide it — pure value-add), Product & BreadcrumbList OFF
     * (PrestaShop themes already emit these natively — re-emitting duplicates the node).
     * Unknown types (e.g. SpeakableSpecification) are always injected.
     */
    private function isSchemaEnabled(string $schemaType): bool
    {
        $map = [
            'FAQPage' => ['FEXA_AI_EMIT_FAQPAGE', '1'],
            'Product' => ['FEXA_AI_EMIT_PRODUCT', '0'],
            'BreadcrumbList' => ['FEXA_AI_EMIT_BREADCRUMB', '0'],
        ];
        if (!isset($map[$schemaType])) {
            return true;
        }
        list($key, $default) = $map[$schemaType];
        $val = Configuration::get($key);
        if ('' === (string) $val) {
            return '1' === $default;
        }

        return (bool) (int) $val;
    }
}
