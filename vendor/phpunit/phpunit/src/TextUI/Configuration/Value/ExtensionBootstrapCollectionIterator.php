<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use function count;
use function iterator_count;
use Countable;
use Iterator;


final class ExtensionBootstrapCollectionIterator implements Countable, Iterator
{
    
    private readonly array $extensionBootstraps;
    private int $position = 0;

    public function __construct(ExtensionBootstrapCollection $extensionBootstraps)
    {
        $this->extensionBootstraps = $extensionBootstraps->asArray();
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
        return $this->position < count($this->extensionBootstraps);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): ExtensionBootstrap
    {
        return $this->extensionBootstraps[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
