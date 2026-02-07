<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;


class OverflowHandler extends AbstractHandler implements FormattableHandlerInterface
{
    private HandlerInterface $handler;

    
    private array $thresholdMap = [];

    
    private array $buffer = [];

    
    public function __construct(
        HandlerInterface $handler,
        array $thresholdMap = [],
        $level = Level::Debug,
        bool $bubble = true
    ) {
        $this->handler = $handler;
        foreach ($thresholdMap as $thresholdLevel => $threshold) {
            $this->thresholdMap[$thresholdLevel] = $threshold;
        }
        parent::__construct($level, $bubble);
    }

    
    public function handle(LogRecord $record): bool
    {
        if ($record->level->isLowerThan($this->level)) {
            return false;
        }

        $level = $record->level->value;

        if (!isset($this->thresholdMap[$level])) {
            $this->thresholdMap[$level] = 0;
        }

        if ($this->thresholdMap[$level] > 0) {
            
            $this->thresholdMap[$level]--;
            $this->buffer[$level][] = $record;

            return false === $this->bubble;
        }

        if ($this->thresholdMap[$level] === 0) {
            
            foreach ($this->buffer[$level] ?? [] as $buffered) {
                $this->handler->handle($buffered);
            }
            $this->thresholdMap[$level]--;
            unset($this->buffer[$level]);
        }

        $this->handler->handle($record);

        return false === $this->bubble;
    }

    
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        if ($this->handler instanceof FormattableHandlerInterface) {
            $this->handler->setFormatter($formatter);

            return $this;
        }

        throw new \UnexpectedValueException('The nested handler of type '.\get_class($this->handler).' does not support formatters.');
    }

    
    public function getFormatter(): FormatterInterface
    {
        if ($this->handler instanceof FormattableHandlerInterface) {
            return $this->handler->getFormatter();
        }

        throw new \UnexpectedValueException('The nested handler of type '.\get_class($this->handler).' does not support formatters.');
    }
}
