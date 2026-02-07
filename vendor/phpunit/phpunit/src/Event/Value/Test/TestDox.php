<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;


final class TestDox
{
    private readonly string $prettifiedClassName;
    private readonly string $prettifiedMethodName;
    private readonly string $prettifiedAndColorizedMethodName;

    public function __construct(string $prettifiedClassName, string $prettifiedMethodName, string $prettifiedAndColorizedMethodName)
    {
        $this->prettifiedClassName              = $prettifiedClassName;
        $this->prettifiedMethodName             = $prettifiedMethodName;
        $this->prettifiedAndColorizedMethodName = $prettifiedAndColorizedMethodName;
    }

    public function prettifiedClassName(): string
    {
        return $this->prettifiedClassName;
    }

    public function prettifiedMethodName(bool $colorize = false): string
    {
        if ($colorize) {
            return $this->prettifiedAndColorizedMethodName;
        }

        return $this->prettifiedMethodName;
    }
}
