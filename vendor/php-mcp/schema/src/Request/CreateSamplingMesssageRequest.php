<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Request;

use PhpMcp\Schema\Constants;
use PhpMcp\Schema\Content\SamplingMessage;
use PhpMcp\Schema\JsonRpc\Request;
use PhpMcp\Schema\ModelPreferences;


class CreateSamplingMesssageRequest extends Request
{
    
    public function __construct(
        string|int $id,
        public readonly array $messages,
        public readonly int $maxTokens,
        public readonly ?ModelPreferences $preferences = null,
        public readonly ?string $systemPrompt = null,
        public readonly ?string $includeContext = null,
        public readonly ?float $temperature = null,
        public readonly ?array $stopSequences = null,
        public readonly ?array $metadata = null,
        public readonly ?array $_meta = null
    ) {
        $params = [
            'messages' => array_map(fn (SamplingMessage $message) => $message->toArray(), $this->messages),
            'maxTokens' => $this->maxTokens,
        ];

        if ($this->preferences !== null) {
            $params['preferences'] = $this->preferences->toArray();
        }

        if ($this->systemPrompt !== null) {
            $params['systemPrompt'] = $this->systemPrompt;
        }

        if ($this->includeContext !== null) {
            $params['includeContext'] = $this->includeContext;
        }

        if ($this->temperature !== null) {
            $params['temperature'] = $this->temperature;
        }

        if ($this->stopSequences !== null) {
            $params['stopSequences'] = $this->stopSequences;
        }

        if ($this->metadata !== null) {
            $params['metadata'] = $this->metadata;
        }

        if ($this->_meta !== null) {
            $params['_meta'] = $this->_meta;
        }

        parent::__construct(Constants::JSONRPC_VERSION, $id, 'sampling/createMessage', $params);
    }

    
    public static function make(string|int $id, array $messages, int $maxTokens, ?ModelPreferences $preferences = null, ?string $systemPrompt = null, ?string $includeContext = null, ?float $temperature = null, ?array $stopSequences = null, ?array $metadata = null, ?array $_meta = null): static
    {
        return new static($id, $messages, $maxTokens, $preferences, $systemPrompt, $includeContext, $temperature, $stopSequences, $metadata, $_meta);
    }
}
