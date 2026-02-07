<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\LogRecord;


class LogglyFormatter extends JsonFormatter
{
    
    public function __construct(int $batchMode = self::BATCH_MODE_NEWLINES, bool $appendNewline = false)
    {
        parent::__construct($batchMode, $appendNewline);
    }

    
    protected function normalizeRecord(LogRecord $record): array
    {
        $recordData = parent::normalizeRecord($record);

        $recordData["timestamp"] = $record->datetime->format("Y-m-d\TH:i:s.uO");
        unset($recordData["datetime"]);

        return $recordData;
    }
}
