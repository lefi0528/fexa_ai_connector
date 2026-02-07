<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;


final class Constant
{
    private readonly string $name;
    private readonly bool|string $value;

    public function __construct(string $name, bool|string $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): bool|string
    {
        return $this->value;
    }
}
