<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\LogRecord;


class SyslogHandler extends AbstractSyslogHandler
{
    protected string $ident;
    protected int $logopts;

    
    public function __construct(string $ident, string|int $facility = LOG_USER, int|string|Level $level = Level::Debug, bool $bubble = true, int $logopts = LOG_PID)
    {
        parent::__construct($facility, $level, $bubble);

        $this->ident = $ident;
        $this->logopts = $logopts;
    }

    
    public function close(): void
    {
        closelog();
    }

    
    protected function write(LogRecord $record): void
    {
        openlog($this->ident, $this->logopts, $this->facility);
        syslog($this->toSyslogPriority($record->level), (string) $record->formatted);
    }
}
