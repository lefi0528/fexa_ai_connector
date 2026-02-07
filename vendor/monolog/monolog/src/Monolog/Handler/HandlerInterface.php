<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\LogRecord;


interface HandlerInterface
{
    
    public function isHandling(LogRecord $record): bool;

    
    public function handle(LogRecord $record): bool;

    
    public function handleBatch(array $records): void;

    
    public function close(): void;
}
