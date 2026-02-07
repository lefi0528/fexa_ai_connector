<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;


final class IniSetting
{
    private readonly string $name;
    private readonly string $value;

    public function __construct(string $name, string $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }
}
