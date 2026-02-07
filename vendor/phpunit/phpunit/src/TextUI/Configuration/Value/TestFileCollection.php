<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use function count;
use Countable;
use IteratorAggregate;


final class TestFileCollection implements Countable, IteratorAggregate
{
    
    private readonly array $files;

    
    public static function fromArray(array $files): self
    {
        return new self(...$files);
    }

    private function __construct(TestFile ...$files)
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

    public function getIterator(): TestFileCollectionIterator
    {
        return new TestFileCollectionIterator($this);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
