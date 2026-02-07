<?php declare(strict_types=1);

namespace PHPUnit\Runner\ResultCache;

use PHPUnit\Framework\TestStatus\TestStatus;


interface ResultCache
{
    public function setStatus(string $id, TestStatus $status): void;

    public function status(string $id): TestStatus;

    public function setTime(string $id, float $time): void;

    public function time(string $id): float;

    public function load(): void;

    public function persist(): void;
}
