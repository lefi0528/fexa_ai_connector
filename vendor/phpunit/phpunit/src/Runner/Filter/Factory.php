<?php declare(strict_types=1);

namespace PHPUnit\Runner\Filter;

use function assert;
use FilterIterator;
use Iterator;
use PHPUnit\Framework\TestSuite;
use ReflectionClass;


final class Factory
{
    
    private array $filters = [];

    
    public function addTestIdFilter(array $testIds): void
    {
        $this->filters[] = [
            new ReflectionClass(TestIdFilterIterator::class), $testIds,
        ];
    }

    
    public function addExcludeGroupFilter(array $groups): void
    {
        $this->filters[] = [
            new ReflectionClass(ExcludeGroupFilterIterator::class), $groups,
        ];
    }

    
    public function addIncludeGroupFilter(array $groups): void
    {
        $this->filters[] = [
            new ReflectionClass(IncludeGroupFilterIterator::class), $groups,
        ];
    }

    
    public function addNameFilter(string $name): void
    {
        $this->filters[] = [
            new ReflectionClass(NameFilterIterator::class), $name,
        ];
    }

    public function factory(Iterator $iterator, TestSuite $suite): FilterIterator
    {
        foreach ($this->filters as $filter) {
            [$class, $arguments] = $filter;
            $iterator            = $class->newInstance($iterator, $arguments, $suite);
        }

        assert($iterator instanceof FilterIterator);

        return $iterator;
    }
}
