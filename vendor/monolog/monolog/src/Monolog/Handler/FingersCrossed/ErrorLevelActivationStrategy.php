<?php declare(strict_types=1);



namespace Monolog\Handler\FingersCrossed;

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Logger;
use Psr\Log\LogLevel;


class ErrorLevelActivationStrategy implements ActivationStrategyInterface
{
    private Level $actionLevel;

    
    public function __construct(int|string|Level $actionLevel)
    {
        $this->actionLevel = Logger::toMonologLevel($actionLevel);
    }

    public function isHandlerActivated(LogRecord $record): bool
    {
        return $record->level->value >= $this->actionLevel->value;
    }
}
