<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\LogRecord;


class MemoryUsageProcessor extends MemoryProcessor
{
    
    public function __invoke(LogRecord $record): LogRecord
    {
        $usage = memory_get_usage($this->realUsage);

        if ($this->useFormatting) {
            $usage = $this->formatBytes($usage);
        }

        $record->extra['memory_usage'] = $usage;

        return $record;
    }
}
