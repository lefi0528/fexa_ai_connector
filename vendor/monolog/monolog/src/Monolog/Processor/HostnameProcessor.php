<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\LogRecord;


class HostnameProcessor implements ProcessorInterface
{
    private static string $host;

    public function __construct()
    {
        self::$host = (string) gethostname();
    }

    
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['hostname'] = self::$host;

        return $record;
    }
}
