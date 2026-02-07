<?php declare(strict_types=1);

namespace PHPUnit\TestRunner\TestResult\Issues;

use PHPUnit\Event\Code\Test;


final class Issue
{
    
    private readonly string $file;

    
    private readonly int $line;

    
    private readonly string $description;

    
    private array $triggeringTests;

    
    public static function from(string $file, int $line, string $description, Test $triggeringTest): self
    {
        return new self($file, $line, $description, $triggeringTest);
    }

    
    private function __construct(string $file, int $line, string $description, Test $triggeringTest)
    {
        $this->file        = $file;
        $this->line        = $line;
        $this->description = $description;

        $this->triggeringTests = [
            $triggeringTest->id() => [
                'test'  => $triggeringTest,
                'count' => 1,
            ],
        ];
    }

    public function triggeredBy(Test $test): void
    {
        if (isset($this->triggeringTests[$test->id()])) {
            $this->triggeringTests[$test->id()]['count']++;

            return;
        }

        $this->triggeringTests[$test->id()] = [
            'test'  => $test,
            'count' => 1,
        ];
    }

    
    public function file(): string
    {
        return $this->file;
    }

    
    public function line(): int
    {
        return $this->line;
    }

    
    public function description(): string
    {
        return $this->description;
    }

    
    public function triggeringTests(): array
    {
        return $this->triggeringTests;
    }
}
