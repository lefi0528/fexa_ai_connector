<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\Level;
use Monolog\LogRecord;


class WildfireFormatter extends NormalizerFormatter
{
    
    public function __construct(?string $dateFormat = null)
    {
        parent::__construct($dateFormat);

        
        $this->removeJsonEncodeOption(JSON_UNESCAPED_UNICODE);
    }

    
    private function toWildfireLevel(Level $level): string
    {
        return match ($level) {
            Level::Debug     => 'LOG',
            Level::Info      => 'INFO',
            Level::Notice    => 'INFO',
            Level::Warning   => 'WARN',
            Level::Error     => 'ERROR',
            Level::Critical  => 'ERROR',
            Level::Alert     => 'ERROR',
            Level::Emergency => 'ERROR',
        };
    }

    
    public function format(LogRecord $record): string
    {
        
        $file = $line = '';
        if (isset($record->extra['file'])) {
            $file = $record->extra['file'];
            unset($record->extra['file']);
        }
        if (isset($record->extra['line'])) {
            $line = $record->extra['line'];
            unset($record->extra['line']);
        }

        $message = ['message' => $record->message];
        $handleError = false;
        if (\count($record->context) > 0) {
            $message['context'] = $this->normalize($record->context);
            $handleError = true;
        }
        if (\count($record->extra) > 0) {
            $message['extra'] = $this->normalize($record->extra);
            $handleError = true;
        }
        if (\count($message) === 1) {
            $message = reset($message);
        }

        if (\is_array($message) && isset($message['context']['table'])) {
            $type  = 'TABLE';
            $label = $record->channel .': '. $record->message;
            $message = $message['context']['table'];
        } else {
            $type  = $this->toWildfireLevel($record->level);
            $label = $record->channel;
        }

        
        $json = $this->toJson([
            [
                'Type'  => $type,
                'File'  => $file,
                'Line'  => $line,
                'Label' => $label,
            ],
            $message,
        ], $handleError);

        
        return sprintf(
            '%d|%s|',
            \strlen($json),
            $json
        );
    }

    
    public function formatBatch(array $records)
    {
        throw new \BadMethodCallException('Batch formatting does not make sense for the WildfireFormatter');
    }

    
    protected function normalize(mixed $data, int $depth = 0): mixed
    {
        if (\is_object($data) && !$data instanceof \DateTimeInterface) {
            return $data;
        }

        return parent::normalize($data, $depth);
    }
}
