<?php

/**
 * Copyright (c) 2025 PrestaShop SA
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by PrestaShop SA. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by PrestaShop SA.
 *
 * The PS MCP Server module was developed by PrestaShop, which holds all associated intellectual property rights. The license granted to the user does not entail any transfer of rights. The user shall refrain from any act that may infringe upon PrestaShop's rights and undertakes to strictly comply with the limitations of the license set out below. PrestaShop grants the user a personal, non-exclusive, non-transferable, and non-sublicensable license to use the MCP Server module, worldwide and for the entire duration of use of the module. This license is strictly limited to installing the module and using it solely for the operation of the user's PrestaShop store.
 */

namespace PrestaShop\Module\FexaAiConnector\Services;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpMcp\Schema\ServerCapabilities;
use PhpMcp\Server\Server;
use PrestaShop\Module\FexaAiConnector\Http\HttpConstants;
use PrestaShop\Module\FexaAiConnector\Server\CustomDiscoverer;
use PrestaShop\Module\FexaAiConnector\Server\CustomFileCache;
use PrestaShop\Module\FexaAiConnector\Server\InMemoryTransport;
use PrestaShop\Module\FexaAiConnector\Tracker\Segment;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class McpService
{
    private const CACHE_FILE_PATH = _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp/.cache_v2.json';
    private const PAGINATION_LIMIT = 999;

    private CacheInterface $cache;
    private Logger $logger;
    private Server $server;
    private McpToolsService $mcpToolsService;
    private McpModulesRegisteredService $mcpModulesRegisteredService;

    private bool $forceRegenCache = false;
    private string $serverVersion;

    public function __construct(\Fexa_ai_connector $module, McpToolsService $mcpToolsService, McpModulesRegisteredService $mcpModulesRegisteredService)
    {
        $this->serverVersion = $module->version;

        if (!file_exists(self::CACHE_FILE_PATH) || filesize(self::CACHE_FILE_PATH) === 0) {
            $this->forceRegenCache = true;
        }

        $this->cache = new CustomFileCache(self::CACHE_FILE_PATH);
        $this->logger = new Logger('mcp');
        $this->mcpToolsService = $mcpToolsService;
        $this->mcpModulesRegisteredService = $mcpModulesRegisteredService;

        if (\Configuration::get('FEXA_AI_SERVER_LOGS_ENABLED')) {
            $this->logger->pushHandler(
                new StreamHandler(
                    _PS_MODULE_DIR_ . 'fexa_ai_connector/.mcp/.logs',
                    Logger::DEBUG
                )
            );
        }
    }

    public function executeHttpMcpRequest(): void
    {
        header(HttpConstants::JSON_CONTENT_TYPE_HEADER);

        if ((bool) \Configuration::get('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER')) {
            $this->fetchAllModulesCompliantWithMcp();
            $this->discover();
        }

        if ((bool) \Configuration::get('FEXA_AI_SERVER_STARTED') === false) {
            http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
            echo json_encode(['error' => 'MCP server is not running']);
            exit;
        }

        $transport = new InMemoryTransport();
        $serverBuilder = Server::make()
            ->withCapabilities(ServerCapabilities::make(
                resources: false,
                resourcesSubscribe: false,
                resourcesListChanged: false,
                prompts: false,
                promptsListChanged: false,
                tools: true,
                toolsListChanged: true,
                logging: false,
                completions: false
            ))
            ->withServerInfo('InMemory Server', $this->serverVersion)
            ->withCache($this->cache)
            ->withPaginationLimit(self::PAGINATION_LIMIT);

        $serverBuilder->withLogger($this->logger);

        $this->server = $serverBuilder->build();

        $hotCachingEnabled = (bool) \Configuration::get('FEXA_AI_SERVER_HOT_CACHING_ENABLED');

        if ($this->forceRegenCache || $hotCachingEnabled) {
            $this->logger->info('🔄 Cache are regenerated');
            $this->discover();
        }

        $this->server->listen($transport, false);
    }

    public function storeNewModuleRegistered(int $moduleId): void
    {
        $module = $this->mcpModulesRegisteredService->getModuleById($moduleId);

        if (!$module) {
            $this->mcpModulesRegisteredService->addModuleId($moduleId);
            \Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
        }
    }

    public function removeModuleRegistered(int $moduleId): void
    {
        $module = $this->mcpModulesRegisteredService->getModuleById($moduleId);

        if ($module) {
            $this->mcpModulesRegisteredService->deleteModuleById($moduleId);
            $this->mcpToolsService->deleteAllToolsByModuleId($moduleId);
            \Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', true);
        }
    }

    public function fetchAllModulesCompliantWithMcp(): void
    {
        $modulesInDb = $this->mcpModulesRegisteredService->getAllModules();
        $modulesInDbIds = array_map(function ($module) {
            return $module['module_id'];
        }, $modulesInDb);

        $modulesInstalled = \Module::getModulesInstalled();
        $modulesInstalledIds = [];

        foreach ($modulesInstalled as $moduleInfos) {
            $module = \Module::getInstanceByName($moduleInfos['name']);

            if (!$module instanceof \Module) {
                continue;
            }

            if (method_exists($module, 'isMcpCompliant') && $module->isMcpCompliant()) {
                $modulesInstalledIds[] = (int) $module->id;

                if (!in_array((int) $module->id, $modulesInDbIds)) {
                    $this->storeNewModuleRegistered((int) $module->id);
                    $this->logger->info('New module using MCP recorded');
                }
            }
        }

        $modulesToUnregister = array_diff($modulesInDbIds, $modulesInstalledIds);

        if (count($modulesToUnregister) > 0) {
            foreach ($modulesToUnregister as $moduleId) {
                $this->removeModuleRegistered($moduleId);
                $this->logger->info('Module uninstalled and unregistered from MCP');
            }
        }
    }

    public function discover(): void
    {
        $this->logger->info('🔄 New discovery started');

        $modulesRegistered = $this->mcpModulesRegisteredService->getAllModules();

        if (!isset($this->server)) {
            $serverBuilder = Server::make()
                ->withCapabilities(ServerCapabilities::make(
                    resources: false,
                    resourcesSubscribe: false,
                    resourcesListChanged: false,
                    prompts: false,
                    promptsListChanged: false,
                    tools: true,
                    toolsListChanged: true,
                    logging: false,
                    completions: false,
                ))
                ->withServerInfo('Discovery Server', $this->serverVersion)
                ->withCache($this->cache)
                ->withLogger($this->logger)
                ->withPaginationLimit(self::PAGINATION_LIMIT);

            $this->server = $serverBuilder->build();
        }

        $serverRegistry = $this->server->getRegistry();
        $customDiscoverer = new CustomDiscoverer($serverRegistry, $this->logger, $this->mcpToolsService, null, null);

        $moduleList = [];

        foreach ($modulesRegistered as $moduleRegistered) {
            $moduleList[] = \Module::getInstanceById($moduleRegistered['module_id']);
        }

        $modulesPathUri = array_map(function ($module) {
            if ($module) {
                return $module->getLocalPath() . 'src';
            }
        }, $moduleList);

        $this->server->discover(_PS_CORE_DIR_, $modulesPathUri, [], false, true, $customDiscoverer);

        $this->logger->info('🔄 Discovery completed', [
            'tools_count' => count($serverRegistry->getTools()),
            'modules_count' => count($modulesRegistered),
        ]);

        \Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', false);
    }
}
