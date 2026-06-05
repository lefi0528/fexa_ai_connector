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
        $this->version = '3.4.4';

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

    public function getContent()
    {
        // Rendered as legacy module content (no Symfony admin controller): works
        // identically on PrestaShop 1.7.8 / 8 / 9. PS 9 removed the controller base
        // class infrastructure used previously, which threw "has no container set".
        $apiKey = (string) Configuration::get('FEXA_AI_API_KEY');
        $safeKey = htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8');

        $intro = $this->l('Optimisez automatiquement votre boutique pour le SEO ET la recherche vocale (Google Assistant, Alexa, Siri) grâce à l\'IA : descriptions, méta, balises ALT et données structurées prêtes pour les moteurs de réponse.');
        $access = $this->l('Accéder à Fexa AI');
        $keyTitle = $this->l('Votre clé API');
        $keyHelp = $this->l('Copiez cette clé et collez-la dans votre tableau de bord Fexa AI pour connecter votre boutique.');
        $copy = $this->l('Copier la clé');

        $featTitle = $this->l('Ce que Fexa AI optimise pour vous');
        $f1t = $this->l('Recherche vocale (AEO)');
        $f1d = $this->l('Balisage Speakable + FAQ pour être lu à voix haute par les assistants vocaux.');
        $f2t = $this->l('Données structurées');
        $f2d = $this->l('JSON-LD Produit, FAQ et fil d\'Ariane injectés automatiquement (rich snippets Google).');
        $f3t = $this->l('SEO automatisé par IA');
        $f3d = $this->l('Titres, méta-descriptions, descriptions et balises ALT réécrits et optimisés.');
        $f4t = $this->l('Moteurs de réponse IA');
        $f4d = $this->l('Contenu prêt pour ChatGPT, Google SGE et les réponses générées par IA.');

        return <<<HTML
<div style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);border-radius:16px;padding:32px;margin-bottom:24px;color:#fff;box-shadow:0 10px 40px rgba(16,185,129,.3);">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
    <div style="flex:1;min-width:300px;">
      <h1 style="margin:0 0 12px 0;font-size:2em;font-weight:800;">🚀 Fexa AI Connector</h1>
      <p style="font-size:1.1em;opacity:.95;margin:0;line-height:1.6;">{$intro}</p>
    </div>
    <a href="https://fexaai.com" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#fff;color:#059669;padding:16px 32px;border-radius:12px;text-decoration:none;font-weight:700;">🌐 {$access}</a>
  </div>
</div>
<div style="background:#fff;border-radius:16px;padding:28px;margin-bottom:24px;border:1px solid #e5e7eb;box-shadow:0 4px 20px rgba(0,0,0,.06);">
  <h3 style="color:#059669;margin:0 0 20px 0;">✨ {$featTitle}</h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
    <div style="background:#f0fdf4;border-radius:12px;padding:18px;border:1px solid #bbf7d0;">
      <div style="font-weight:700;color:#065f46;margin-bottom:6px;">🎙️ {$f1t}</div>
      <div style="color:#4b5563;font-size:.95em;line-height:1.5;">{$f1d}</div>
    </div>
    <div style="background:#f0fdf4;border-radius:12px;padding:18px;border:1px solid #bbf7d0;">
      <div style="font-weight:700;color:#065f46;margin-bottom:6px;">📊 {$f2t}</div>
      <div style="color:#4b5563;font-size:.95em;line-height:1.5;">{$f2d}</div>
    </div>
    <div style="background:#f0fdf4;border-radius:12px;padding:18px;border:1px solid #bbf7d0;">
      <div style="font-weight:700;color:#065f46;margin-bottom:6px;">🤖 {$f3t}</div>
      <div style="color:#4b5563;font-size:.95em;line-height:1.5;">{$f3d}</div>
    </div>
    <div style="background:#f0fdf4;border-radius:12px;padding:18px;border:1px solid #bbf7d0;">
      <div style="font-weight:700;color:#065f46;margin-bottom:6px;">💬 {$f4t}</div>
      <div style="color:#4b5563;font-size:.95em;line-height:1.5;">{$f4d}</div>
    </div>
  </div>
</div>
<div style="background:#fff;border-radius:16px;padding:28px;margin-bottom:24px;border:2px solid #10b981;box-shadow:0 4px 20px rgba(0,0,0,.08);">
  <h3 style="color:#059669;margin:0 0 12px 0;">🔑 {$keyTitle}</h3>
  <p style="color:#4b5563;margin:0 0 16px 0;">{$keyHelp}</p>
  <input id="fexa-api-key" type="text" readonly value="{$safeKey}" onclick="this.select()" style="width:100%;background:#f3f4f6;padding:14px 18px;font-size:1.1em;border-radius:10px;border:1px solid #e5e7eb;font-family:monospace;color:#1f2937;box-sizing:border-box;"/>
  <button type="button" class="btn btn-primary" style="margin-top:16px;" onclick="var e=document.getElementById('fexa-api-key');e.select();document.execCommand('copy');this.innerHTML='✅';">📋 {$copy}</button>
</div>
HTML;
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
