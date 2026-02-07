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

    public function __construct()
    {
        $this->name = 'fexa_ai_connector';
        $this->author = 'Fexa AI';
        $this->tab = 'seo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->version = '1.0.0';

        parent::__construct();

        $this->displayName = $this->l('Fexa AI Connector');
        $this->description = $this->l('Connect your store with Fexa AI services.');
        $this->confirmUninstall = $this->l('Do you really want to uninstall Fexa AI Connector?');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.99.99'];
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

    public function getContent(): void
    {
        if ($this->context == null || $this->context->link == null) {
            throw new PrestaShopException('Context or Link not defined');
        }

        // Note: We need to rename the controller URL too later
        Tools::redirectAdmin($this->context->link->getAdminLink('FexaAiConfigController', true, ['route' => 'fexa_ai_config']));
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
        $sql = preg_split("/;\s*[\r\n]+/", trim($sql));

        $success = true;

        if (!empty($sql)) {
            foreach ($sql as $query) {
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

        if (empty($sql)) {
            return false;
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", trim($sql));

        $success = true;

        if (!empty($sql)) {
            foreach ($sql as $query) {
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
        if (version_compare(_PS_VERSION_, '9.0', '>=')) {
            return $this->get($serviceName);
        }

        $splitServiceNamespace = explode('.', $serviceName);
        $firstLevelNamespace = $splitServiceNamespace[0];

        if ($firstLevelNamespace !== 'ps_metrics' && $firstLevelNamespace !== 'ps_accounts') {
            try {
                $service = $this->serviceContainer->getService($serviceName);
            } catch (Exception $e) {
                $container = SymfonyContainer::getInstance();

                if ($container == null) {
                    throw new PrestaShopException('Symfony container is null or invalid');
                }

                $service = $container->get($serviceName);
            }

            return $service;
        }

        return $this->serviceContainer->getService($serviceName);
    }

    public function getHooksList(): array
    {
        return [
            'moduleRoutes',
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
}
