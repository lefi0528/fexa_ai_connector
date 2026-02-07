<?php

declare(strict_types=1);

namespace PhpMcp\Client\Cache;

use PhpMcp\Client\Exception\DefinitionException;
use PhpMcp\Client\Model\Definitions\PromptDefinition;
use PhpMcp\Client\Model\Definitions\ResourceDefinition;
use PhpMcp\Client\Model\Definitions\ResourceTemplateDefinition;
use PhpMcp\Client\Model\Definitions\ToolDefinition;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;


final class DefinitionCache
{
    private const CACHE_KEY_PREFIX = 'mcp_client_defs_';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $ttl, 
        private readonly LoggerInterface $logger
    ) {}

    
    public function getTools(string $serverName): ?array
    {
        return $this->get($serverName, 'tools', ToolDefinition::class);
    }

    
    public function setTools(string $serverName, array $tools): void
    {
        $this->set($serverName, 'tools', $tools);
    }

    
    public function getResources(string $serverName): ?array
    {
        return $this->get($serverName, 'resources', ResourceDefinition::class);
    }

    
    public function setResources(string $serverName, array $resources): void
    {
        $this->set($serverName, 'resources', $resources);
    }

    
    public function getPrompts(string $serverName): ?array
    {
        return $this->get($serverName, 'prompts', PromptDefinition::class);
    }

    
    public function setPrompts(string $serverName, array $prompts): void
    {
        $this->set($serverName, 'prompts', $prompts);
    }

    
    public function getResourceTemplates(string $serverName): ?array
    {
        return $this->get($serverName, 'res_templates', ResourceTemplateDefinition::class);
    }

    
    public function setResourceTemplates(string $serverName, array $templates): void
    {
        $this->set($serverName, 'res_templates', $templates);
    }

    
    private function get(string $serverName, string $type, string $expectedClass): ?array
    {
        $key = $this->generateCacheKey($serverName, $type);
        try {
            $cachedData = $this->cache->get($key);

            if ($cachedData === null) {
                return null; 
            }

            if (! is_array($cachedData)) {
                $this->logger->warning("Invalid data type found in cache for {$key}. Expected array.", ['type' => gettype($cachedData)]);
                $this->cache->delete($key); 

                return null;
            }

            $definitions = [];
            foreach ($cachedData as $itemData) {
                if (! is_array($itemData)) {
                    $this->logger->warning("Invalid item data type found in cached array for {$key}. Expected array.", ['type' => gettype($itemData)]);
                    $this->cache->delete($key); 

                    return null;
                }
                
                if (! method_exists($expectedClass, 'fromArray')) {
                    throw new DefinitionException("Definition class {$expectedClass} is missing the required fromArray method.");
                }

                $definitions[] = call_user_func([$expectedClass, 'fromArray'], $itemData);
            }

            return $definitions;

        } catch (Throwable $e) {
            
            $this->logger->error("Error getting definitions from cache for key '{$key}': {$e->getMessage()}", ['exception' => $e]);

            return null; 
        }
    }

    
    private function set(string $serverName, string $type, array $definitions): void
    {
        $key = $this->generateCacheKey($serverName, $type);
        try {
            
            $dataToCache = array_map(function ($definition) {
                if (! method_exists($definition, 'toArray')) {
                    throw new DefinitionException('Definition class '.get_class($definition).' is missing the required toArray method for caching.');
                }

                return $definition->toArray(); 
            }, $definitions);

            $this->cache->set($key, $dataToCache, $this->ttl);
        } catch (Throwable $e) {
            
            $this->logger->error("Error setting definitions to cache for key '{$key}': {$e->getMessage()}", ['exception' => $e]);
        }
    }

    private function generateCacheKey(string $serverName, string $type): string
    {
        
        $safeServerName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $serverName);

        return self::CACHE_KEY_PREFIX.$safeServerName.'_'.$type;
    }

    
    public function invalidateServerCache(string $serverName): void
    {
        $keys = [
            $this->generateCacheKey($serverName, 'tools'),
            $this->generateCacheKey($serverName, 'resources'),
            $this->generateCacheKey($serverName, 'prompts'),
            $this->generateCacheKey($serverName, 'res_templates'),
        ];
        try {
            $this->cache->deleteMultiple($keys);
            $this->logger->info("Invalidated definition cache for server '{$serverName}'.");
        } catch (Throwable $e) {
            $this->logger->error("Error invalidating cache for server '{$serverName}': {$e->getMessage()}", ['exception' => $e]);
        }
    }
}
