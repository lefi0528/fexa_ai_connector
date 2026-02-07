<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;


final class Group
{
    private readonly string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }
}
