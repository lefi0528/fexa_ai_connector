<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\LogRecord;


interface FormatterInterface
{
    
    public function format(LogRecord $record);

    
    public function formatBatch(array $records);
}
