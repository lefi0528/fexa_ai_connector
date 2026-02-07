<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\LogRecord;


class MemoryPeakUsageProcessor extends MemoryProcessor
{
    
    public function __invoke(LogRecord $record): LogRecord
    {
        $usage = memory_get_peak_usage($this->realUsage);

        if ($this->useFormatting) {
            $usage = $this->formatBytes($usage);
        }

        $record->extra['memory_peak_usage'] = $usage;

        return $record;
    }
}
