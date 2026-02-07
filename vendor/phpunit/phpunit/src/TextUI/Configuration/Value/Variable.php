<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;


final class Variable
{
    private readonly string $name;
    private readonly mixed $value;
    private readonly bool $force;

    public function __construct(string $name, mixed $value, bool $force)
    {
        $this->name  = $name;
        $this->value = $value;
        $this->force = $force;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function force(): bool
    {
        return $this->force;
    }
}
