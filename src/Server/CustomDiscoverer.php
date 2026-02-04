<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Server;

use PhpMcp\Schema\Prompt;
use PhpMcp\Schema\PromptArgument;
use PhpMcp\Schema\Resource;
use PhpMcp\Schema\ResourceTemplate;
use PhpMcp\Schema\Tool;
use PhpMcp\Server\Attributes\CompletionProvider;
use PhpMcp\Server\Attributes\McpPrompt;
use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpResourceTemplate;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Defaults\EnumCompletionProvider;
use PhpMcp\Server\Defaults\ListCompletionProvider;
use PhpMcp\Server\Exception\McpServerException;
use PhpMcp\Server\Registry;
use PhpMcp\Server\Utils\Discoverer;
use PhpMcp\Server\Utils\DocBlockParser;
use PhpMcp\Server\Utils\SchemaGenerator;
use PrestaShop\Module\FexaAiConnector\Services\McpToolsService;
use PrestaShop\Module\FexaAiConnector\Tracker\Segment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomDiscoverer extends Discoverer
{
    private DocBlockParser $docBlockParser;
    private SchemaGenerator $schemaGenerator;
    private McpToolsService $mcpToolsService;
    private array $discoveredTools = [];

    public function __construct(
        private Registry $registry,
        private LoggerInterface $logger,
        McpToolsService $mcpToolsService,
        ?DocBlockParser $docBlockParser,
        ?SchemaGenerator $schemaGenerator,
    ) {
        $this->mcpToolsService = $mcpToolsService;
        $this->docBlockParser = $docBlockParser ?? new DocBlockParser($this->logger);
        $this->schemaGenerator = $schemaGenerator ?? new SchemaGenerator($this->docBlockParser);
    }

    public function discover(string $basePath, array $directories, array $excludeDirs = []): void
    {
        $startTime = microtime(true);
        $discoveredCount = [
            'tools' => 0,
            'resources' => 0,
            'prompts' => 0,
            'resourceTemplates' => 0,
        ];

        try {
            $finder = new Finder();
            $absolutePaths = [];

            foreach ($directories as $dir) {
                // Check for Unix absolute path OR Windows absolute path (Drive letter)
                if (str_starts_with($dir, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $dir)) {
                    $path = $dir;
                } else {
                    $path = rtrim($basePath, '/') . '/' . ltrim($dir, '/');
                }

                $this->logger->info('Module path currently discovered: ' . $path);

                if (is_dir($path)) {
                    $absolutePaths[] = $path;
                }
            }

            if (empty($absolutePaths)) {
                $this->logger->warning('No valid discovery directories found to scan.', ['configured_paths' => $directories, 'base_path' => $basePath]);

                return;
            }

            $finder->files()
                ->in($absolutePaths)
                ->exclude($excludeDirs)
                ->name('*.php')
                ->notName([
                    'index.php',
                ])
            ;

            foreach ($finder as $file) {
                $this->processFile($file, $discoveredCount);
            }

            $this->mcpToolsService->cleanObsoleteTools($this->discoveredTools);
        } catch (\Throwable $e) {
            $this->logger->error('Error during file finding process for MCP discovery', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $duration = microtime(true) - $startTime;
        $this->logger->info('Attribute discovery finished.', [
            'duration_sec' => round($duration, 3),
            'tools' => $discoveredCount['tools'],
            'resources' => $discoveredCount['resources'],
            'prompts' => $discoveredCount['prompts'],
            'resourceTemplates' => $discoveredCount['resourceTemplates'],
        ]);
    }

    private function processFile(\SplFileInfo $file, array &$discoveredCount): void
    {
        $filePath = $file->getRealPath();
        if ($filePath === false) {
            $this->logger->warning('Could not get real path for file', ['path' => $file->getPathname()]);

            return;
        }

        $className = $this->getClassFromFile($filePath);
        if (!$className) {
            $this->logger->warning('No valid class found in file', ['file' => $filePath]);

            return;
        }

        try {
            $reflectionClass = new \ReflectionClass($className);

            if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait() || $reflectionClass->isEnum()) {
                return;
            }

            $processedViaClassAttribute = false;
            if ($reflectionClass->hasMethod('__invoke')) {
                $invokeMethod = $reflectionClass->getMethod('__invoke');
                if ($invokeMethod->isPublic() && !$invokeMethod->isStatic()) {
                    $attributeTypes = [McpTool::class, McpResource::class, McpPrompt::class, McpResourceTemplate::class];
                    foreach ($attributeTypes as $attributeType) {
                        $classAttribute = $reflectionClass->getAttributes($attributeType, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
                        if ($classAttribute) {
                            $this->processMethod($invokeMethod, $discoveredCount, $classAttribute);
                            $processedViaClassAttribute = true;
                            break;
                        }
                    }
                }
            }

            if (!$processedViaClassAttribute) {
                foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if (
                        $method->getDeclaringClass()->getName() !== $reflectionClass->getName()
                        || $method->isStatic() || $method->isAbstract() || $method->isConstructor() || $method->isDestructor() || $method->getName() === '__invoke'
                    ) {
                        continue;
                    }
                    $attributeTypes = [McpTool::class, McpResource::class, McpPrompt::class, McpResourceTemplate::class];
                    foreach ($attributeTypes as $attributeType) {
                        $methodAttribute = $method->getAttributes($attributeType, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
                        if ($methodAttribute) {
                            $this->processMethod($method, $discoveredCount, $methodAttribute);
                            break;
                        }
                    }
                }
            }
        } catch (\ReflectionException $e) {
            $this->logger->error('Reflection error processing file for MCP discovery', ['file' => $filePath, 'class' => $className, 'exception' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error processing file for MCP discovery', [
                'file' => $filePath,
                'class' => $className,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function processMethod(\ReflectionMethod $method, array &$discoveredCount, \ReflectionAttribute $attribute): void
    {
        $attributeClassName = $attribute->getName();

        switch ($attributeClassName) {
            case McpTool::class:
                $this->processTool($method, $discoveredCount, $attribute, $attributeClassName);
                break;

            case McpPrompt::class:
                $this->processPrompt($method, $discoveredCount, $attribute, $attributeClassName);
                break;

            case McpResource::class:
                $this->processResource($method, $discoveredCount, $attribute, $attributeClassName);
                break;

            case McpResourceTemplate::class:
                $this->processResourceTemplate($method, $discoveredCount, $attribute, $attributeClassName);
                break;

            default:
                break;
        }
    }

    private function getCompletionProviders(\ReflectionMethod $reflectionMethod): array
    {
        $completionProviders = [];
        foreach ($reflectionMethod->getParameters() as $param) {
            $reflectionType = $param->getType();
            if ($reflectionType instanceof \ReflectionNamedType && !$reflectionType->isBuiltin()) {
                continue;
            }

            $completionAttributes = $param->getAttributes(CompletionProvider::class, \ReflectionAttribute::IS_INSTANCEOF);
            if (!empty($completionAttributes)) {
                $attributeInstance = $completionAttributes[0]->newInstance();

                if ($attributeInstance->provider) {
                    $completionProviders[$param->getName()] = $attributeInstance->provider;
                } elseif ($attributeInstance->providerClass) {
                    $completionProviders[$param->getName()] = $attributeInstance->provider;
                } elseif ($attributeInstance->values) {
                    $completionProviders[$param->getName()] = new ListCompletionProvider($attributeInstance->values);
                } elseif ($attributeInstance->enum) {
                    $completionProviders[$param->getName()] = new EnumCompletionProvider($attributeInstance->enum);
                }
            }
        }

        return $completionProviders;
    }

    private function getClassFromFile(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->logger->warning('File does not exist or is not readable.', ['file' => $filePath]);

            return null;
        }

        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                $this->logger->warning('Failed to read file content.', ['file' => $filePath]);

                return null;
            }
            if (strlen($content) > 500 * 1024) {
                $this->logger->debug('Skipping large file during class discovery.', ['file' => $filePath]);

                return null;
            }

            $tokens = token_get_all($content);
        } catch (\Throwable $e) {
            $this->logger->warning("Failed to read or tokenize file during class discovery: {$filePath}", ['exception' => $e->getMessage()]);

            return null;
        }

        $namespace = '';
        $namespaceFound = false;
        $level = 0;
        $potentialClasses = [];

        $tokenCount = count($tokens);
        for ($i = 0; $i < $tokenCount; ++$i) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $tokenCount; ++$j) {
                    if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        $namespaceFound = true;
                        $i = $j;
                        break;
                    }
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED])) {
                        $namespace .= $tokens[$j][1];
                    } elseif ($tokens[$j][0] === T_NS_SEPARATOR) {
                        $namespace .= '\\';
                    }
                }
                if ($namespaceFound) {
                    break;
                }
            }
        }
        $namespace = trim($namespace, '\\');

        for ($i = 0; $i < $tokenCount; ++$i) {
            $token = $tokens[$i];
            if ($token === '{') {
                ++$level;

                continue;
            }
            if ($token === '}') {
                --$level;

                continue;
            }

            if (
                $level === ($namespaceFound && str_contains($content, "namespace {$namespace} {") ? 1 : 0)
                && is_array($token)
                && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, defined('T_ENUM') ? T_ENUM : -1])
            ) {
                for ($j = $i + 1; $j < $tokenCount; ++$j) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                        $potentialClasses[] = $namespace ? $namespace . '\\' . $className : $className;
                        $i = $j;
                        break;
                    }
                    if ($tokens[$j] === ';' || $tokens[$j] === '{' || $tokens[$j] === ')') {
                        break;
                    }
                }
            }
        }

        foreach ($potentialClasses as $potentialClass) {
            if (class_exists($potentialClass, true)) {
                return $potentialClass;
            }
        }

        if (!empty($potentialClasses)) {
            if (!class_exists($potentialClasses[0], false)) {
                $this->logger->debug('getClassFromFile returning potential non-class type. Are you sure this class has been autoloaded?', ['file' => $filePath, 'type' => $potentialClasses[0]]);
            }

            return $potentialClasses[0];
        }

        return null;
    }

    private function processTool(\ReflectionMethod $method, array &$discoveredCount, \ReflectionAttribute $attribute, string $attributeClassName)
    {
        $className = $method->getDeclaringClass()->getName();
        $classShortName = $method->getDeclaringClass()->getShortName();
        $methodName = $method->getName();

        try {
            $instance = $attribute->newInstance();

            $docBlock = $this->docBlockParser->parseDocBlock($method->getDocComment() ?: null);
            $name = $instance->name ?? ($methodName === '__invoke' ? $classShortName : $methodName);
            $description = $instance->description ?? $this->docBlockParser->getSummary($docBlock) ?? null;
            $inputSchema = $this->schemaGenerator->generate($method);
            $tool = Tool::make($name, $inputSchema, $description, $instance->annotations);

            $folderModuleName = $this->getModuleNameFromClass($className);
            $module = \Module::getInstanceByName($folderModuleName);

            if (!$module) {
                $this->logger->error("Failed to process MCP attribute for class {$className}, module not found on shop");

                return;
            }

            $toolAlreadyRegistered = $this->mcpToolsService->getToolByNameAndModuleId($tool->name, $module->id);

            if (!$toolAlreadyRegistered) {
                $this->mcpToolsService->registerTool((int) $module->id, $tool->name, $tool->description);
                $this->registry->registerTool($tool, [$className, $methodName]);
                ++$discoveredCount['tools'];
            } elseif ($toolAlreadyRegistered && $toolAlreadyRegistered['is_active']) {
                $this->registry->registerTool($tool, [$className, $methodName]);
                ++$discoveredCount['tools'];
            }

            $this->discoveredTools[] = [
                'name' => $tool->name,
                'module_id' => (int) $module->id,
            ];
        } catch (McpServerException $e) {
            $this->logger->error("Failed to process MCP attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getPrevious() ? $e->getPrevious()->getTraceAsString() : $e->getTraceAsString()]);
        } catch (\Throwable $e) {
            $this->logger->error("Unexpected error processing attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function processPrompt(\ReflectionMethod $method, array &$discoveredCount, \ReflectionAttribute $attribute, string $attributeClassName)
    {
        $className = $method->getDeclaringClass()->getName();
        $classShortName = $method->getDeclaringClass()->getShortName();
        $methodName = $method->getName();

        try {
            $instance = $attribute->newInstance();

            $docBlock = $this->docBlockParser->parseDocBlock($method->getDocComment() ?: null);
            $name = $instance->name ?? ($methodName === '__invoke' ? $classShortName : $methodName);
            $description = $instance->description ?? $this->docBlockParser->getSummary($docBlock) ?? null;
            $arguments = [];
            $paramTags = $this->docBlockParser->getParamTags($docBlock);
            foreach ($method->getParameters() as $param) {
                $reflectionType = $param->getType();
                if ($reflectionType instanceof \ReflectionNamedType && !$reflectionType->isBuiltin()) {
                    continue;
                }
                $paramTag = $paramTags['$' . $param->getName()] ?? null;
                $arguments[] = PromptArgument::make($param->getName(), $paramTag ? trim((string) $paramTag->getDescription()) : null, !$param->isOptional() && !$param->isDefaultValueAvailable());
            }
            $prompt = Prompt::make($name, $description, $arguments);
            $completionProviders = $this->getCompletionProviders($method);
            $this->registry->registerPrompt($prompt, [$className, $methodName], $completionProviders);
            ++$discoveredCount['prompts'];
        } catch (McpServerException $e) {
            $this->logger->error("Failed to process MCP attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getPrevious() ? $e->getPrevious()->getTraceAsString() : $e->getTraceAsString()]);
        } catch (\Throwable $e) {
            $this->logger->error("Unexpected error processing attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function processResource(\ReflectionMethod $method, array &$discoveredCount, \ReflectionAttribute $attribute, string $attributeClassName)
    {
        $className = $method->getDeclaringClass()->getName();
        $classShortName = $method->getDeclaringClass()->getShortName();
        $methodName = $method->getName();

        try {
            $instance = $attribute->newInstance();

            $docBlock = $this->docBlockParser->parseDocBlock($method->getDocComment() ?: null);
            $name = $instance->name ?? ($methodName === '__invoke' ? $classShortName : $methodName);
            $description = $instance->description ?? $this->docBlockParser->getSummary($docBlock) ?? null;
            $mimeType = $instance->mimeType;
            $size = $instance->size;
            $annotations = $instance->annotations;
            $resource = Resource::make($instance->uri, $name, $description, $mimeType, $annotations, $size);
            $this->registry->registerResource($resource, [$className, $methodName]);
            ++$discoveredCount['resources'];
        } catch (McpServerException $e) {
            $this->logger->error("Failed to process MCP attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getPrevious() ? $e->getPrevious()->getTraceAsString() : $e->getTraceAsString()]);
        } catch (\Throwable $e) {
            $this->logger->error("Unexpected error processing attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function processResourceTemplate(\ReflectionMethod $method, array &$discoveredCount, \ReflectionAttribute $attribute, string $attributeClassName)
    {
        $className = $method->getDeclaringClass()->getName();
        $classShortName = $method->getDeclaringClass()->getShortName();
        $methodName = $method->getName();

        try {
            $instance = $attribute->newInstance();

            $docBlock = $this->docBlockParser->parseDocBlock($method->getDocComment() ?: null);
            $name = $instance->name ?? ($methodName === '__invoke' ? $classShortName : $methodName);
            $description = $instance->description ?? $this->docBlockParser->getSummary($docBlock) ?? null;
            $mimeType = $instance->mimeType;
            $annotations = $instance->annotations;
            $resourceTemplate = ResourceTemplate::make($instance->uriTemplate, $name, $description, $mimeType, $annotations);
            $completionProviders = $this->getCompletionProviders($method);
            $this->registry->registerResourceTemplate($resourceTemplate, [$className, $methodName], $completionProviders);
            ++$discoveredCount['resourceTemplates'];
        } catch (McpServerException $e) {
            $this->logger->error("Failed to process MCP attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getPrevious() ? $e->getPrevious()->getTraceAsString() : $e->getTraceAsString()]);
        } catch (\Throwable $e) {
            $this->logger->error("Unexpected error processing attribute on {$className}::{$methodName}", ['attribute' => $attributeClassName, 'exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function getModuleNameFromClass(string $className): ?string
    {
        $ref = new \ReflectionClass($className);
        $filePath = $ref->getFileName();

        if (!$filePath) {
            return null;
        }

        $filePath = str_replace('\\', '/', $filePath);

        $pos = strpos($filePath, '/modules/');
        if ($pos === false) {
            return null;
        }

        $relativePath = substr($filePath, $pos + 9);
        $parts = explode('/', $relativePath);

        return $parts[0] ?? null;
    }
}
