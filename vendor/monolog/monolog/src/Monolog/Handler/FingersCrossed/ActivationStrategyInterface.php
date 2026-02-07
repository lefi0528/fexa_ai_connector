<?php declare(strict_types=1);



namespace Monolog\Handler\FingersCrossed;

use Monolog\LogRecord;


interface ActivationStrategyInterface
{
    
    public function isHandlerActivated(LogRecord $record): bool;
}
