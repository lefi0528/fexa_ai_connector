<?php declare(strict_types=1);

namespace PHPUnit\Event\TestData;

use function count;
use Countable;
use IteratorAggregate;


final class TestDataCollection implements Countable, IteratorAggregate
{
    
    private readonly array $data;
    private ?DataFromDataProvider $fromDataProvider = null;

    
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    
    private function __construct(TestData ...$data)
    {
        $this->ensureNoMoreThanOneDataFromDataProvider($data);

        $this->data = $data;
    }

    
    public function asArray(): array
    {
        return $this->data;
    }

    public function count(): int
    {
        return count($this->data);
    }

    
    public function hasDataFromDataProvider(): bool
    {
        return $this->fromDataProvider !== null;
    }

    
    public function dataFromDataProvider(): DataFromDataProvider
    {
        if (!$this->hasDataFromDataProvider()) {
            throw new NoDataSetFromDataProviderException;
        }

        return $this->fromDataProvider;
    }

    public function getIterator(): TestDataCollectionIterator
    {
        return new TestDataCollectionIterator($this);
    }

    
    private function ensureNoMoreThanOneDataFromDataProvider(array $data): void
    {
        foreach ($data as $_data) {
            if ($_data->isFromDataProvider()) {
                if ($this->fromDataProvider !== null) {
                    throw new MoreThanOneDataSetFromDataProviderException;
                }

                $this->fromDataProvider = $_data;
            }
        }
    }
}
