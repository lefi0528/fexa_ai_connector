<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Psr\Log\LogLevel;
use Monolog\Logger;
use Monolog\LogRecord;


class NullHandler extends Handler
{
    private Level $level;

    
    public function __construct(string|int|Level $level = Level::Debug)
    {
        $this->level = Logger::toMonologLevel($level);
    }

    
    public function isHandling(LogRecord $record): bool
    {
        return $record->level->value >= $this->level->value;
    }

    
    public function handle(LogRecord $record): bool
    {
        return $record->level->value >= $this->level->value;
    }
}
