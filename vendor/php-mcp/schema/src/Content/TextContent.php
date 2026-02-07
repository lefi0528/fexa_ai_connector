<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Content;

use PhpMcp\Schema\Annotations;


class TextContent extends Content
{
    
    public function __construct(
        public readonly string $text,
        public readonly ?Annotations $annotations = null
    ) {
        parent::__construct('text');
    }

    
    public function toArray(): array
    {
        $result = [
            'type' => 'text',
            'text' => $this->text,
        ];

        if ($this->annotations !== null) {
            $result['annotations'] = $this->annotations->toArray();
        }

        return $result;
    }

    public static function fromArray(array $data): static
    {
        if (!isset($data['text']) || !is_string($data['text'])) {
            throw new \InvalidArgumentException("Missing or invalid 'text' in TextContent data.");
        }

        return new static($data['text'], $data['annotations'] ?? null);
    }

    
    public static function make(mixed $value, ?Annotations $annotations = null): static
    {
        if (is_array($value) || is_object($value)) {
            $text = json_encode($value, JSON_PRETTY_PRINT);

            return new static($text, $annotations);
        }

        return new static((string) $value, $annotations);
    }

    
    public static function code(string $code, string $language = '', ?Annotations $annotations = null): static
    {
        return new static("```{$language}\n{$code}\n```", $annotations);
    }
}
