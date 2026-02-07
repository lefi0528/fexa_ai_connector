<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Rollbar\RollbarLogger;
use Throwable;
use Monolog\LogRecord;


class RollbarHandler extends AbstractProcessingHandler
{
    protected RollbarLogger $rollbarLogger;

    
    private bool $hasRecords = false;

    protected bool $initialized = false;

    
    public function __construct(RollbarLogger $rollbarLogger, int|string|Level $level = Level::Error, bool $bubble = true)
    {
        $this->rollbarLogger = $rollbarLogger;

        parent::__construct($level, $bubble);
    }

    
    protected function toRollbarLevel(Level $level): string
    {
        return match ($level) {
            Level::Debug     => 'debug',
            Level::Info      => 'info',
            Level::Notice    => 'info',
            Level::Warning   => 'warning',
            Level::Error     => 'error',
            Level::Critical  => 'critical',
            Level::Alert     => 'critical',
            Level::Emergency => 'critical',
        };
    }

    
    protected function write(LogRecord $record): void
    {
        if (!$this->initialized) {
            
            register_shutdown_function([$this, 'close']);
            $this->initialized = true;
        }

        $context = $record->context;
        $context = array_merge($context, $record->extra, [
            'level' => $this->toRollbarLevel($record->level),
            'monolog_level' => $record->level->getName(),
            'channel' => $record->channel,
            'datetime' => $record->datetime->format('U'),
        ]);

        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $exception = $context['exception'];
            unset($context['exception']);
            $toLog = $exception;
        } else {
            $toLog = $record->message;
        }

        $this->rollbarLogger->log($context['level'], $toLog, $context);

        $this->hasRecords = true;
    }

    public function flush(): void
    {
        if ($this->hasRecords) {
            $this->rollbarLogger->flush();
            $this->hasRecords = false;
        }
    }

    
    public function close(): void
    {
        $this->flush();
    }

    
    public function reset(): void
    {
        $this->flush();

        parent::reset();
    }
}
