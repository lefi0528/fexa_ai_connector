<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Logger;
use Monolog\ResettableInterface;
use Psr\Log\LogLevel;
use Monolog\LogRecord;


abstract class AbstractHandler extends Handler implements ResettableInterface
{
    protected Level $level = Level::Debug;
    protected bool $bubble = true;

    
    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->setLevel($level);
        $this->bubble = $bubble;
    }

    
    public function isHandling(LogRecord $record): bool
    {
        return $record->level->value >= $this->level->value;
    }

    
    public function setLevel(int|string|Level $level): self
    {
        $this->level = Logger::toMonologLevel($level);

        return $this;
    }

    
    public function getLevel(): Level
    {
        return $this->level;
    }

    
    public function setBubble(bool $bubble): self
    {
        $this->bubble = $bubble;

        return $this;
    }

    
    public function getBubble(): bool
    {
        return $this->bubble;
    }

    
    public function reset(): void
    {
    }
}
