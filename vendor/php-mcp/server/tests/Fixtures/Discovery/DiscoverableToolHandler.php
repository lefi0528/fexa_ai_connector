<?php

declare(strict_types=1);

namespace PhpMcp\Server\Tests\Fixtures\Discovery;

use PhpMcp\Schema\ToolAnnotations;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Tests\Fixtures\Enums\BackedStringEnum;

class DiscoverableToolHandler
{
    
    #[McpTool(name: "greet_user", description: "Greets a user by name.")]
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }

    
    #[McpTool(annotations: new ToolAnnotations(readOnlyHint: true))]
    public function repeatAction(int $count, bool $loudly = false, BackedStringEnum $mode = BackedStringEnum::OptionA): array
    {
        return ['count' => $count, 'loudly' => $loudly, 'mode' => $mode->value, 'message' => "Action repeated."];
    }

    
    public function internalHelperMethod(int $value): int
    {
        return $value * 2;
    }

    #[McpTool(name: "private_tool_should_be_ignored")] 
    private function aPrivateTool(): void
    {
    }

    #[McpTool(name: "protected_tool_should_be_ignored")] 
    protected function aProtectedTool(): void
    {
    }

    #[McpTool(name: "static_tool_should_be_ignored")] 
    public static function aStaticTool(): void
    {
    }
}
