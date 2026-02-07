<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use const PHP_EOL;
use function sprintf;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;


final class PhpDeprecationTriggered implements Event
{
    private readonly Telemetry\Info $telemetryInfo;
    private readonly Test $test;

    
    private readonly string $message;

    
    private readonly string $file;

    
    private readonly int $line;
    private readonly bool $suppressed;
    private readonly bool $ignoredByBaseline;
    private readonly bool $ignoredByTest;

    
    public function __construct(Telemetry\Info $telemetryInfo, Test $test, string $message, string $file, int $line, bool $suppressed, bool $ignoredByBaseline, bool $ignoredByTest)
    {
        $this->telemetryInfo     = $telemetryInfo;
        $this->test              = $test;
        $this->message           = $message;
        $this->file              = $file;
        $this->line              = $line;
        $this->suppressed        = $suppressed;
        $this->ignoredByBaseline = $ignoredByBaseline;
        $this->ignoredByTest     = $ignoredByTest;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function test(): Test
    {
        return $this->test;
    }

    
    public function message(): string
    {
        return $this->message;
    }

    
    public function file(): string
    {
        return $this->file;
    }

    
    public function line(): int
    {
        return $this->line;
    }

    public function wasSuppressed(): bool
    {
        return $this->suppressed;
    }

    public function ignoredByBaseline(): bool
    {
        return $this->ignoredByBaseline;
    }

    public function ignoredByTest(): bool
    {
        return $this->ignoredByTest;
    }

    public function asString(): string
    {
        $message = $this->message;

        if (!empty($message)) {
            $message = PHP_EOL . $message;
        }

        $status = '';

        if ($this->ignoredByTest) {
            $status = 'Test-Ignored ';
        } elseif ($this->ignoredByBaseline) {
            $status = 'Baseline-Ignored ';
        } elseif ($this->suppressed) {
            $status = 'Suppressed ';
        }

        return sprintf(
            'Test Triggered %sPHP Deprecation (%s) in %s:%d%s',
            $status,
            $this->test->id(),
            $this->file,
            $this->line,
            $message,
        );
    }
}
