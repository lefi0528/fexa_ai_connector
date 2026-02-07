<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;

use PHPUnit\TextUI\Configuration\GroupCollection;


final class Groups
{
    private readonly GroupCollection $include;
    private readonly GroupCollection $exclude;

    public function __construct(GroupCollection $include, GroupCollection $exclude)
    {
        $this->include = $include;
        $this->exclude = $exclude;
    }

    public function hasInclude(): bool
    {
        return !$this->include->isEmpty();
    }

    public function include(): GroupCollection
    {
        return $this->include;
    }

    public function hasExclude(): bool
    {
        return !$this->exclude->isEmpty();
    }

    public function exclude(): GroupCollection
    {
        return $this->exclude;
    }
}
