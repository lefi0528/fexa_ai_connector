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

namespace PrestaShop\Module\FexaAiConnector\Services;

use Module;
use PhpMcp\Schema\ServerCapabilities;
use PhpMcp\Server\Server;
use PrestaShop\Module\FexaAiConnector\Http\HttpConstants;
use PrestaShop\Module\FexaAiConnector\Server\CustomDiscoverer;
use PrestaShop\Module\FexaAiConnector\Server\CustomFileCache;
use PrestaShop\Module\FexaAiConnector\Server\FexaToolContainer;
use PrestaShop\Module\FexaAiConnector\Server\InMemoryTransport;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
    private LoggerInterface $logger;
    private Server $server;
    private McpToolsService $mcpToolsService;
    private McpModulesRegisteredService $mcpModulesRegisteredService;

    private bool $forceRegenCache = false;
    private string $serverVersion;

    public function __construct(\Fexa_ai_connector $module, McpToolsService $mcpToolsService, McpModulesRegisteredService $mcpModulesRegisteredService)
    {
        $this->serverVersion = $module->version;

        if (!file_exists(self::CACHE_FILE_PATH) || 0 === filesize(self::CACHE_FILE_PATH)) {
            $this->forceRegenCache = true;
        }

        $this->cache = new CustomFileCache(self::CACHE_FILE_PATH);
        // PSR-3 logger. We intentionally do NOT use Monolog here: PrestaShop core
        // ships its own (older) Monolog, and bundling Monolog in the module made
        // its classes shadow the core ones, causing a fatal Compile Error on
        // PS8/PS9 (SyslogFormatter::format signature mismatch). php-mcp only needs
        // a Psr\Log\LoggerInterface, so a NullLogger is enough and conflict-free.
        $this->logger = new NullLogger();
        $this->mcpToolsService = $mcpToolsService;
        $this->mcpModulesRegisteredService = $mcpModulesRegisteredService;
    }

    /**
     * @param mixed $psContext the live PrestaShop context, passed in by the front
     *                         controller (McpServer) so the tool handlers can be
     *                         injected with it instead of reaching for a global accessor
     */
    public function executeHttpMcpRequest($psContext = null): void
    {
        header(HttpConstants::JSON_CONTENT_TYPE_HEADER);

        // Fail fast with an actionable message if the module's cache/log directory
        // is not writable — the #1 cause of a blank 500 on shared hosting / first run.
        $cacheDir = dirname(self::CACHE_FILE_PATH);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
            http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);
            echo json_encode([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Le dossier du module n\'est pas inscriptible : ' . $cacheDir
                        . '. Donnez les droits d\'écriture (chmod 755) à ce dossier puis réessayez.',
                ],
                'id' => null,
            ]);

            return;
        }

        try {
            if ((bool) \Configuration::get('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER')) {
                $this->fetchAllModulesCompliantWithMcp();
                $this->discover();
            }

            if (false === (bool) \Configuration::get('FEXA_AI_SERVER_STARTED')) {
                http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
                echo json_encode(['error' => 'MCP server is not running']);

                return;
            }

            $transport = new InMemoryTransport($psContext);
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

            // Inject the live shop context into the tool handlers. php-mcp instantiates
            // each handler via this container (RegisteredElement::handle -> container->get),
            // so the tools receive the context here instead of calling a global accessor.
            $serverBuilder->withContainer(new FexaToolContainer($psContext));

            $this->server = $serverBuilder->build();

            $hotCachingEnabled = (bool) \Configuration::get('FEXA_AI_SERVER_HOT_CACHING_ENABLED');

            if ($this->forceRegenCache || $hotCachingEnabled) {
                $this->logger->info('Cache are regenerated');
                $this->discover();
            }

            $this->server->listen($transport, false);
        } catch (\Throwable $e) {
            // Never leak a blank 500: return the real reason as a JSON-RPC error so the
            // SaaS connection screen (and our logs) can show exactly what failed on PS 9.x.
            $this->logger->error('MCP request failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (!headers_sent()) {
                http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            echo json_encode([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Erreur interne du module Fexa AI : ' . $e->getMessage(),
                    'data' => [
                        'file' => basename($e->getFile()),
                        'line' => $e->getLine(),
                        'type' => get_class($e),
                    ],
                ],
                'id' => null,
            ]);
        }
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
        $this->logger->info('New discovery started');

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

        $this->logger->info('Discovery completed', [
            'tools_count' => count($serverRegistry->getTools()),
            'modules_count' => count($modulesRegistered),
        ]);

        \Configuration::updateValue('FEXA_AI_SERVER_TOOLS_NEED_DISCOVER', false);
    }
}
