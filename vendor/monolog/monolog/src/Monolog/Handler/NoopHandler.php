<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\LogRecord;


class NoopHandler extends Handler
{
    
    public function isHandling(LogRecord $record): bool
    {
        return true;
    }

    
    public function handle(LogRecord $record): bool
    {
        return false;
    }
}
