<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use function count;
use function iterator_count;
use Countable;
use Iterator;


final class FileCollectionIterator implements Countable, Iterator
{
    
    private readonly array $files;
    private int $position = 0;

    public function __construct(FileCollection $files)
    {
        $this->files = $files->asArray();
    }

    public function count(): int
    {
        return iterator_count($this);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->position < count($this->files);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): File
    {
        return $this->files[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
