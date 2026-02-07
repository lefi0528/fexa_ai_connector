<?php declare(strict_types=1);

namespace PHPUnit\Runner\Filter;

use function in_array;


final class IncludeGroupFilterIterator extends GroupFilterIterator
{
    protected function doAccept(int $id): bool
    {
        return in_array($id, $this->groupTests, true);
    }
}
