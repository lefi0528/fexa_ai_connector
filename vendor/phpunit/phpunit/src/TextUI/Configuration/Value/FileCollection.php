<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use function count;
use Countable;
use IteratorAggregate;


final class FileCollection implements Countable, IteratorAggregate
{
    
    private readonly array $files;

    
    public static function fromArray(array $files): self
    {
        return new self(...$files);
    }

    private function __construct(File ...$files)
    {
        $this->files = $files;
    }

    
    public function asArray(): array
    {
        return $this->files;
    }

    public function count(): int
    {
        return count($this->files);
    }

    public function notEmpty(): bool
    {
        return !empty($this->files);
    }

    public function getIterator(): FileCollectionIterator
    {
        return new FileCollectionIterator($this);
    }
}
