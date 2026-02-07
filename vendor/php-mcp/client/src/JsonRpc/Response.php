<?php

declare(strict_types=1);

namespace PhpMcp\Client\JsonRpc;

use Psy\Readline\Hoa\ProtocolException;

final class Response extends Message
{
    
    public function __construct(
        public string|int|null $id,
        public mixed $result = null,
        public ?Error $error = null,
    ) {
        
        if ($this->result !== null && $this->error !== null) {
            
        }
        if ($this->result === null && $this->error === null && $this->id !== null) {
            
        }
    }

    public function isSuccess(): bool
    {
        return $this->error === null && $this->result !== null;
    }

    public function isError(): bool
    {
        return $this->error !== null;
    }

    
    public static function fromArray(array $data): self
    {
        if (! isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            throw new ProtocolException('Invalid or missing "jsonrpc" version in response. Must be "2.0".');
        }

        
        $id = $data['id'] ?? null;
        if (! (is_string($id) || is_int($id) || $id === null)) {
            throw new ProtocolException('Invalid "id" field in response.');
        }

        $hasResult = array_key_exists('result', $data);
        $hasError = array_key_exists('error', $data);

        if ($hasResult && $hasError) {
            throw new ProtocolException('Invalid response: contains both "result" and "error".');
        }
        if (! $hasResult && ! $hasError) {
            throw new ProtocolException('Invalid response: must contain either "result" or "error".');
        }

        $error = null;
        $result = null;

        if ($hasError) {
            if (! is_array($data['error'])) { 
                throw new ProtocolException('Invalid "error" field in response: must be an object.');
            }
            $error = Error::fromArray($data['error']);
        } else {
            
            $result = $data['result'];
        }

        return new self($id, $result, $error);
    }

    public function toArray(): array 
    {
        $payload = ['jsonrpc' => $this->jsonrpc, 'id' => $this->id];
        if ($this->error !== null) {
            $payload['error'] = $this->error->toArray();
        } else {
            
            $payload['result'] = $this->result;
        }

        return $payload;
    }
}
