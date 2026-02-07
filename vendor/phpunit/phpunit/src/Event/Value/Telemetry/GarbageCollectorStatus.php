<?php declare(strict_types=1);

namespace PHPUnit\Event\Telemetry;

use PHPUnit\Event\RuntimeException;


final class GarbageCollectorStatus
{
    private readonly int $runs;
    private readonly int $collected;
    private readonly int $threshold;
    private readonly int $roots;
    private readonly ?float $applicationTime;
    private readonly ?float $collectorTime;
    private readonly ?float $destructorTime;
    private readonly ?float $freeTime;
    private readonly ?bool $running;
    private readonly ?bool $protected;
    private readonly ?bool $full;
    private readonly ?int $bufferSize;

    public function __construct(int $runs, int $collected, int $threshold, int $roots, ?float $applicationTime, ?float $collectorTime, ?float $destructorTime, ?float $freeTime, ?bool $running, ?bool $protected, ?bool $full, ?int $bufferSize)
    {
        $this->runs            = $runs;
        $this->collected       = $collected;
        $this->threshold       = $threshold;
        $this->roots           = $roots;
        $this->applicationTime = $applicationTime;
        $this->collectorTime   = $collectorTime;
        $this->destructorTime  = $destructorTime;
        $this->freeTime        = $freeTime;
        $this->running         = $running;
        $this->protected       = $protected;
        $this->full            = $full;
        $this->bufferSize      = $bufferSize;
    }

    public function runs(): int
    {
        return $this->runs;
    }

    public function collected(): int
    {
        return $this->collected;
    }

    public function threshold(): int
    {
        return $this->threshold;
    }

    public function roots(): int
    {
        return $this->roots;
    }

    
    public function hasExtendedInformation(): bool
    {
        return $this->running !== null;
    }

    
    public function applicationTime(): float
    {
        if ($this->applicationTime === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->applicationTime;
    }

    
    public function collectorTime(): float
    {
        if ($this->collectorTime === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->collectorTime;
    }

    
    public function destructorTime(): float
    {
        if ($this->destructorTime === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->destructorTime;
    }

    
    public function freeTime(): float
    {
        if ($this->freeTime === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->freeTime;
    }

    
    public function isRunning(): bool
    {
        if ($this->running === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->running;
    }

    
    public function isProtected(): bool
    {
        if ($this->protected === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->protected;
    }

    
    public function isFull(): bool
    {
        if ($this->full === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->full;
    }

    
    public function bufferSize(): int
    {
        if ($this->bufferSize === null) {
            throw new RuntimeException('Information not available');
        }

        return $this->bufferSize;
    }
}
