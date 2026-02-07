<?php declare(strict_types=1);



namespace Monolog\Formatter;

use DateTimeInterface;
use Monolog\LogRecord;


class GoogleCloudLoggingFormatter extends JsonFormatter
{
    protected function normalizeRecord(LogRecord $record): array
    {
        $normalized = parent::normalizeRecord($record);

        
        $normalized['severity'] = $normalized['level_name'];
        $normalized['time'] = $record->datetime->format(DateTimeInterface::RFC3339_EXTENDED);

        
        unset($normalized['level'], $normalized['level_name'], $normalized['datetime']);

        return $normalized;
    }
}
