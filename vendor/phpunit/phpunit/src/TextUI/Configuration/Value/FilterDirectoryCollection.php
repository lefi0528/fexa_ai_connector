<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use function count;
use Countable;
use IteratorAggregate;


final class FilterDirectoryCollection implements Countable, IteratorAggregate
{
    
    private readonly array $directories;

    
    public static function fromArray(array $directories): self
    {
        return new self(...$directories);
    }

    private function __construct(FilterDirectory ...$directories)
    {
        $this->directories = $directories;
    }

    
    public function asArray(): array
    {
        return $this->directories;
    }

    public function count(): int
    {
        return count($this->directories);
    }

    public function notEmpty(): bool
    {
        return !empty($this->directories);
    }

    public function getIterator(): FilterDirectoryCollectionIterator
    {
        return new FilterDirectoryCollectionIterator($this);
    }
}
