<?php

declare(strict_types=1);

namespace PhpMcp\Server\Tests\Fixtures\Discovery;

use PhpMcp\Server\Attributes\McpPrompt;
use PhpMcp\Server\Attributes\McpResourceTemplate;
use PhpMcp\Server\Attributes\CompletionProvider;
use PhpMcp\Server\Tests\Fixtures\Enums\StatusEnum;
use PhpMcp\Server\Tests\Fixtures\Enums\PriorityEnum;

class EnhancedCompletionHandler
{
    
    #[McpPrompt(name: 'content_creator')]
    public function createContent(
        #[CompletionProvider(values: ['blog', 'article', 'tutorial', 'guide'])]
        string $type,
        #[CompletionProvider(enum: StatusEnum::class)]
        string $status,
        #[CompletionProvider(enum: PriorityEnum::class)]
        string $priority
    ): array {
        return [
            ['role' => 'user', 'content' => "Create a {$type} with status {$status} and priority {$priority}"]
        ];
    }

    
    #[McpResourceTemplate(
        uriTemplate: 'content://{category}/{slug}',
        name: 'content_template'
    )]
    public function getContent(
        #[CompletionProvider(values: ['news', 'blog', 'docs', 'api'])]
        string $category,
        string $slug
    ): array {
        return [
            'category' => $category,
            'slug' => $slug,
            'url' => "https://example.com/{$category}/{$slug}"
        ];
    }
}
