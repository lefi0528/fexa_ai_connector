<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Result;

use PhpMcp\Schema\Content\AudioContent;
use PhpMcp\Schema\Content\ImageContent;
use PhpMcp\Schema\Content\TextContent;
use PhpMcp\Schema\Enum\Role;
use PhpMcp\Schema\JsonRpc\Result;


class CreateSamplingMesssageResult extends Result
{
    
    public function __construct(
        public readonly Role $role,
        public readonly TextContent|ImageContent|AudioContent $content,
        public readonly string $model,
        public readonly ?string $stopReason = null,
    ) {
    }

    
    public static function make(Role $role, TextContent|ImageContent|AudioContent $content, string $model, ?string $stopReason = null): static
    {
        return new static($role, $content, $model, $stopReason);
    }

    public function toArray(): array
    {
        $result = [
            'role' => $this->role->value,
            'content' => $this->content->toArray(),
            'model' => $this->model
        ];

        if ($this->stopReason !== null) {
            $result['stopReason'] = $this->stopReason;
        }
        return $result;
    }
}
