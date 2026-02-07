<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\LogRecord;


interface ProcessorInterface
{
    
    public function __invoke(LogRecord $record);
}
