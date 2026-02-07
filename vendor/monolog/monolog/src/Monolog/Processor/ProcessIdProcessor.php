<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\LogRecord;


class ProcessIdProcessor implements ProcessorInterface
{
    
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['process_id'] = getmypid();

        return $record;
    }
}
