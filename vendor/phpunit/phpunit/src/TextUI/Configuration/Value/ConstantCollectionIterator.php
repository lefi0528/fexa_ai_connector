<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use function count;
use function iterator_count;
use Countable;
use Iterator;


final class ConstantCollectionIterator implements Countable, Iterator
{
    
    private readonly array $constants;
    private int $position = 0;

    public function __construct(ConstantCollection $constants)
    {
        $this->constants = $constants->asArray();
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
        return $this->position < count($this->constants);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): Constant
    {
        return $this->constants[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
