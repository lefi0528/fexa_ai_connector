<?php
/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 *
 * This module was developed by Fexa AI.
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

    const GITHUB_REPO = 'lefi0528/fexa_ai_connector';
    const UPDATE_CHECK_CACHE_KEY = 'FEXA_AI_LATEST_VERSION';
    const UPDATE_CHECK_CACHE_TTL = 86400; // 24h

    public function __construct()
    {
        $this->name = 'fexa_ai_connector';
        $this->author = 'Fexa AI';
        $this->tab = 'seo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->version = '3.4.0';

        parent::__construct();

        $this->displayName = $this->l('Fexa AI Connector');
        $this->description = $this->l('Connect your store with Fexa AI services.');
        $this->confirmUninstall = $this->l('Do you really want to uninstall Fexa AI Connector?');
        // Compatible PrestaShop 1.7.8 → 9.x. Requires PHP 8.1+ (php-mcp/server).
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => '9.99.99'];
        $this->adminControllers = [];

        if ($this->serviceContainer === null) {
            $this->serviceContainer = new ServiceContainer(
                (string) $this->name,
                $this->getLocalPath()
            );
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
            && Configuration::deleteByName('FEXA_AI_API_KEY')
            && Configuration::deleteByName(self::UPDATE_CHECK_CACHE_KEY)
            && Configuration::deleteByName(self::UPDATE_CHECK_CACHE_KEY . '_TS');
    }

    public function upgrade($version): bool
    {
        Configuration::updateValue('FEXA_AI_SERVER_FIRST_DISCOVERY_DONE', false);
        return true;
    }

    /**
     * Called by PrestaShop's module update checker.
     * Returns the latest available version from GitHub Releases.
     */
    public function checkVersion(): string
    {
        $cachedVersion = Configuration::get(self::UPDATE_CHECK_CACHE_KEY);
        $cachedTs = (int) Configuration::get(self::UPDATE_CHECK_CACHE_KEY . '_TS');

        if ($cachedVersion && (time() - $cachedTs) < self::UPDATE_CHECK_CACHE_TTL) {
            return $cachedVersion;
        }

        try {
            $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: PrestaShop-Module-FexaAI\r\n",
                    'timeout' => 5,
                ]
            ];
            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);

            if ($response) {
                $data = json_decode($response, true);
                $latestVersion = ltrim($data['tag_name'] ?? $this->version, 'v');

                Configuration::updateValue(self::UPDATE_CHECK_CACHE_KEY, $latestVersion);
                Configuration::updateValue(self::UPDATE_CHECK_CACHE_KEY . '_TS', time());

                return $latestVersion;
            }
        } catch (\Exception $e) {
            // Silently fail — return current version to avoid false update alerts
        }

        return $this->version;
    }

    /**
     * Returns the download URL for the latest release ZIP from GitHub.
     */
    public function getUpdateUrl(): string
    {
        return 'https://github.com/' . self::GITHUB_REPO . '/releases/latest/download/fexa_ai_connector.zip';
    }

    public function isMcpCompliant(): bool
    {
        return true;
    }

    public function getMultistoreCompatibility(): int
    {
        return (int) true;
    }

    public function getContent(): void
    {
        if ($this->context == null || $this->context->link == null) {
            throw new PrestaShopException('Context or Link not defined');
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminFexaAiConfig', true, ['route' => 'admin_fexa_ai_connector_config']));
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

        $sql = (string) \Tools::file_get_contents($dbInstallFile);

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
                if (empty($query)) continue;
                if (!\Db::getInstance()->execute($query)) {
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

        $sql = (string) \Tools::file_get_contents($dbUninstallFile);

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]*/", $sql);

        $success = true;

        if (!empty($sql)) {
            foreach ($sql as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                if (!\Db::getInstance()->execute($query)) {
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

        return (bool) \Db::getInstance()->executeS($query);
    }

    public function getService($serviceName)
    {
        try {
            return $this->serviceContainer->getService($serviceName);
        } catch (\Exception $e) {
            // Pas grave, on continue
        }

        if (version_compare(_PS_VERSION_, '8.0.0', '>=')) {
            $container = SymfonyContainer::getInstance();
            if ($container !== null) {
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
            'SELECT `jsonld` FROM `' . _DB_PREFIX_ . 'fexa_ai_structured_data` '
            . "WHERE entity_type='" . pSQL($controller) . "' AND entity_id=" . $entityId
            . ' AND is_active=1 AND (id_lang=' . $idLang . ' OR id_lang=0) '
            . 'AND (id_shop=' . $idShop . ' OR id_shop=0)'
        );

        if (!is_array($rows) || count($rows) === 0) {
            return '';
        }

        $out = '';
        foreach ($rows as $row) {
            // Prevent premature </script> termination (the only way JSON-LD could break out).
            $safe = str_replace('</', '<\\/', (string) $row['jsonld']);
            $out .= '<script type="application/ld+json">' . $safe . '</script>' . "\n";
        }

        return $out;
    }
}
