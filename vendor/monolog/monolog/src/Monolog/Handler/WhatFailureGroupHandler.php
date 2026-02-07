<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\LogRecord;
use Throwable;


class WhatFailureGroupHandler extends GroupHandler
{
    
    public function handle(LogRecord $record): bool
    {
        if (\count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }

        foreach ($this->handlers as $handler) {
            try {
                $handler->handle(clone $record);
            } catch (Throwable) {
                
            }
        }

        return false === $this->bubble;
    }

    
    public function handleBatch(array $records): void
    {
        if (\count($this->processors) > 0) {
            $processed = [];
            foreach ($records as $record) {
                $processed[] = $this->processRecord($record);
            }
            $records = $processed;
        }

        foreach ($this->handlers as $handler) {
            try {
                $handler->handleBatch(array_map(fn ($record) => clone $record, $records));
            } catch (Throwable) {
                
            }
        }
    }

    
    public function close(): void
    {
        foreach ($this->handlers as $handler) {
            try {
                $handler->close();
            } catch (\Throwable $e) {
                
            }
        }
    }
}
