<?php

namespace React\Async;


interface FiberInterface
{
    public function resume(mixed $value): void;

    public function throw(\Throwable $throwable): void;

    public function suspend(): mixed;
}
