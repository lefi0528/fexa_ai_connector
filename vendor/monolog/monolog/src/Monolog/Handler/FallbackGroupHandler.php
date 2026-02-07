<?php declare(strict_types=1);



namespace Monolog\Handler;

use Throwable;
use Monolog\LogRecord;


class FallbackGroupHandler extends GroupHandler
{
    
    public function handle(LogRecord $record): bool
    {
        if (\count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }
        foreach ($this->handlers as $handler) {
            try {
                $handler->handle(clone $record);
                break;
            } catch (Throwable $e) {
                
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
                break;
            } catch (Throwable $e) {
                
            }
        }
    }
}
