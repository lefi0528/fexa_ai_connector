<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Level;
use Monolog\LogRecord;


class ZendMonitorHandler extends AbstractProcessingHandler
{
    
    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        if (!\function_exists('zend_monitor_custom_event')) {
            throw new MissingExtensionException(
                'You must have Zend Server installed with Zend Monitor enabled in order to use this handler'
            );
        }

        parent::__construct($level, $bubble);
    }

    
    protected function toZendMonitorLevel(Level $level): int
    {
        return match ($level) {
            Level::Debug     => \ZEND_MONITOR_EVENT_SEVERITY_INFO,
            Level::Info      => \ZEND_MONITOR_EVENT_SEVERITY_INFO,
            Level::Notice    => \ZEND_MONITOR_EVENT_SEVERITY_INFO,
            Level::Warning   => \ZEND_MONITOR_EVENT_SEVERITY_WARNING,
            Level::Error     => \ZEND_MONITOR_EVENT_SEVERITY_ERROR,
            Level::Critical  => \ZEND_MONITOR_EVENT_SEVERITY_ERROR,
            Level::Alert     => \ZEND_MONITOR_EVENT_SEVERITY_ERROR,
            Level::Emergency => \ZEND_MONITOR_EVENT_SEVERITY_ERROR,
        };
    }

    
    protected function write(LogRecord $record): void
    {
        $this->writeZendMonitorCustomEvent(
            $record->level->getName(),
            $record->message,
            $record->formatted,
            $this->toZendMonitorLevel($record->level)
        );
    }

    
    protected function writeZendMonitorCustomEvent(string $type, string $message, array $formatted, int $severity): void
    {
        zend_monitor_custom_event($type, $message, $formatted, $severity);
    }

    
    public function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter();
    }
}
