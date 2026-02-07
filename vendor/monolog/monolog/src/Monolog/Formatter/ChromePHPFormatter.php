<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\Level;
use Monolog\LogRecord;


class ChromePHPFormatter implements FormatterInterface
{
    
    private function toWildfireLevel(Level $level): string
    {
        return match ($level) {
            Level::Debug     => 'log',
            Level::Info      => 'info',
            Level::Notice    => 'info',
            Level::Warning   => 'warn',
            Level::Error     => 'error',
            Level::Critical  => 'error',
            Level::Alert     => 'error',
            Level::Emergency => 'error',
        };
    }

    
    public function format(LogRecord $record)
    {
        
        $backtrace = 'unknown';
        if (isset($record->extra['file'], $record->extra['line'])) {
            $backtrace = $record->extra['file'].' : '.$record->extra['line'];
            unset($record->extra['file'], $record->extra['line']);
        }

        $message = ['message' => $record->message];
        if (\count($record->context) > 0) {
            $message['context'] = $record->context;
        }
        if (\count($record->extra) > 0) {
            $message['extra'] = $record->extra;
        }
        if (\count($message) === 1) {
            $message = reset($message);
        }

        return [
            $record->channel,
            $message,
            $backtrace,
            $this->toWildfireLevel($record->level),
        ];
    }

    
    public function formatBatch(array $records)
    {
        $formatted = [];

        foreach ($records as $record) {
            $formatted[] = $this->format($record);
        }

        return $formatted;
    }
}
