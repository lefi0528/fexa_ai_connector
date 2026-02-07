<?php declare(strict_types=1);

namespace PHPUnit\Runner\Filter;

use function array_map;
use function array_push;
use function in_array;
use function spl_object_id;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use RecursiveFilterIterator;
use RecursiveIterator;


abstract class GroupFilterIterator extends RecursiveFilterIterator
{
    
    protected array $groupTests = [];

    
    public function __construct(RecursiveIterator $iterator, array $groups, TestSuite $suite)
    {
        parent::__construct($iterator);

        foreach ($suite->groupDetails() as $group => $tests) {
            if (in_array((string) $group, $groups, true)) {
                $testHashes = array_map(
                    'spl_object_id',
                    $tests,
                );

                array_push($this->groupTests, ...$testHashes);
            }
        }
    }

    public function accept(): bool
    {
        $test = $this->getInnerIterator()->current();

        if ($test instanceof TestSuite) {
            return true;
        }

        return $this->doAccept(spl_object_id($test));
    }

    abstract protected function doAccept(int $id): bool;
}
