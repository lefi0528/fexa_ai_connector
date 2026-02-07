<?php

declare(strict_types=1);

namespace PhpMcp\Server\Elements;

use PhpMcp\Schema\Content\Content;
use PhpMcp\Schema\Content\TextContent;
use Psr\Container\ContainerInterface;
use PhpMcp\Schema\Tool;
use Throwable;

class RegisteredTool extends RegisteredElement
{
    public function __construct(
        public readonly Tool $schema,
        callable|array|string $handler,
        bool $isManual = false,
    ) {
        parent::__construct($handler, $isManual);
    }

    public static function make(Tool $schema, callable|array|string $handler, bool $isManual = false): self
    {
        return new self($schema, $handler, $isManual);
    }

    
    public function call(ContainerInterface $container, array $arguments): array
    {
        $result = $this->handle($container, $arguments);

        return $this->formatResult($result);
    }

    
    protected function formatResult(mixed $toolExecutionResult): array
    {
        if ($toolExecutionResult instanceof Content) {
            return [$toolExecutionResult];
        }

        if (is_array($toolExecutionResult)) {
            if (empty($toolExecutionResult)) {
                return [TextContent::make('[]')];
            }

            $allAreContent = true;
            $hasContent = false;

            foreach ($toolExecutionResult as $item) {
                if ($item instanceof Content) {
                    $hasContent = true;
                } else {
                    $allAreContent = false;
                }
            }

            if ($allAreContent && $hasContent) {
                return $toolExecutionResult;
            }

            if ($hasContent) {
                $result = [];
                foreach ($toolExecutionResult as $item) {
                    if ($item instanceof Content) {
                        $result[] = $item;
                    } else {
                        $result = array_merge($result, $this->formatResult($item));
                    }
                }
                return $result;
            }
        }

        if ($toolExecutionResult === null) {
            return [TextContent::make('(null)')];
        }

        if (is_bool($toolExecutionResult)) {
            return [TextContent::make($toolExecutionResult ? 'true' : 'false')];
        }

        if (is_scalar($toolExecutionResult)) {
            return [TextContent::make($toolExecutionResult)];
        }

        $jsonResult = json_encode(
            $toolExecutionResult,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return [TextContent::make($jsonResult)];
    }

    public function toArray(): array
    {
        return [
            'schema' => $this->schema->toArray(),
            ...parent::toArray(),
        ];
    }

    public static function fromArray(array $data): self|false
    {
        try {
            if (! isset($data['schema']) || ! isset($data['handler'])) {
                return false;
            }

            return new self(
                Tool::fromArray($data['schema']),
                $data['handler'],
                $data['isManual'] ?? false,
            );
        } catch (Throwable $e) {
            return false;
        }
    }
}
