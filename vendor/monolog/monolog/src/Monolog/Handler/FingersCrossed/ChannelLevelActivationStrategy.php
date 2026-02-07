<?php declare(strict_types=1);



namespace Monolog\Handler\FingersCrossed;

use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Monolog\LogRecord;


class ChannelLevelActivationStrategy implements ActivationStrategyInterface
{
    private Level $defaultActionLevel;

    
    private array $channelToActionLevel;

    
    public function __construct(int|string|Level $defaultActionLevel, array $channelToActionLevel = [])
    {
        $this->defaultActionLevel = Logger::toMonologLevel($defaultActionLevel);
        $this->channelToActionLevel = array_map(Logger::toMonologLevel(...), $channelToActionLevel);
    }

    public function isHandlerActivated(LogRecord $record): bool
    {
        if (isset($this->channelToActionLevel[$record->channel])) {
            return $record->level->value >= $this->channelToActionLevel[$record->channel]->value;
        }

        return $record->level->value >= $this->defaultActionLevel->value;
    }
}
