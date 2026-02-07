<?php

declare(strict_types=1);

namespace PhpMcp\Client\Model;

use stdClass;


final class Capabilities
{
    
    public ?array $roots = null;

    
    public ?array $sampling = null;

    
    public ?array $experimental = null;

    
    public ?array $tools = null;

    
    public ?array $resources = null;

    
    public ?array $prompts = null;

    
    public ?array $logging = null;

    
    private function __construct() {}

    
    public static function forClient(
        bool $supportsSampling = true,
        ?bool $supportsRootListChanged = null,
        ?array $experimental = null
    ): self {
        $caps = new self;

        if ($supportsSampling) {
            $caps->sampling = [];
        }

        if ($supportsRootListChanged !== null) {
            $caps->roots = ['listChanged' => $supportsRootListChanged];
        }

        $caps->experimental = $experimental;

        return $caps;
    }

    
    public static function fromServerResponse(array $data): self
    {
        $caps = new self;
        $caps->prompts = isset($data['prompts']) && is_array($data['prompts']) ? $data['prompts'] : null;
        $caps->resources = isset($data['resources']) && is_array($data['resources']) ? $data['resources'] : null;
        $caps->tools = isset($data['tools']) && is_array($data['tools']) ? $data['tools'] : null;
        $caps->logging = isset($data['logging']) && is_array($data['logging']) ? $data['logging'] : null;
        $caps->experimental = isset($data['experimental']) && is_array($data['experimental']) ? $data['experimental'] : null;

        $caps->roots = null;
        $caps->sampling = null;

        return $caps;
    }

    
    public function toClientArray(): array|stdClass
    {
        $data = [];
        if ($this->roots !== null) {
            $data['roots'] = $this->roots;
        }
        if ($this->sampling !== null) {
            $data['sampling'] = empty($this->sampling) ? new stdClass : $this->sampling;
        }
        if ($this->experimental !== null) {
            $data['experimental'] = $this->experimental;
        }

        return empty($data) ? new stdClass : $data;
    }

    public function serverSupportsTools(): bool
    {
        return $this->tools !== null;
    }

    public function serverSupportsToolListChanged(): bool
    {
        return $this->tools['listChanged'] ?? false;
    }

    public function serverSupportsResources(): bool
    {
        return $this->resources !== null;
    }

    public function serverSupportsResourceSubscription(): bool
    {
        return $this->resources['subscribe'] ?? false;
    }

    public function serverSupportsResourceListChanged(): bool
    {
        return $this->resources['listChanged'] ?? false;
    }

    public function serverSupportsPrompts(): bool
    {
        return $this->prompts !== null;
    }

    public function serverSupportsPromptListChanged(): bool
    {
        return $this->prompts['listChanged'] ?? false;
    }

    public function serverSupportsLogging(): bool
    {
        return $this->logging !== null;
    }
}
