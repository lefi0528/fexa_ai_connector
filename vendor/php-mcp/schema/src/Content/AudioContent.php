<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Content;

use PhpMcp\Schema\Annotations;


class AudioContent extends Content
{
    public function __construct(
        public readonly string $data,
        public readonly string $mimeType,
        public readonly ?Annotations $annotations = null
    ) {
        parent::__construct('audio');
    }

    public static function make(string $data, string $mimeType, ?Annotations $annotations = null): static
    {
        return new static($data, $mimeType, $annotations);
    }

    
    public function toArray(): array
    {
        $result = [
            'type' => 'audio',
            'data' => $this->data,
            'mimeType' => $this->mimeType,
        ];

        if ($this->annotations !== null) {
            $result['annotations'] = $this->annotations->toArray();
        }

        return $result;
    }

    public static function fromArray(array $data): static
    {
        if (! isset($data['data']) || ! isset($data['mimeType'])) {
            throw new \InvalidArgumentException("Invalid or missing 'data' or 'mimeType' in AudioContent data.");
        }

        return new static($data['data'], $data['mimeType'], $data['annotations'] ?? null);
    }

    
    public static function fromFile(string $path, ?string $mimeType = null, ?Annotations $annotations = null): static
    {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Audio file not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Could not read audio file: {$path}");
        }
        $data = base64_encode($content);
        $detectedMime = $mimeType ?? mime_content_type($path) ?: 'application/octet-stream';

        return new static($data, $detectedMime, $annotations);
    }

    
    public static function fromString(string $data, string $mimeType, ?Annotations $annotations = null): static
    {
        return new static(base64_encode($data), $mimeType, $annotations);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
