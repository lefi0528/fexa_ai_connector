<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\LogRecord;


class LogmaticFormatter extends JsonFormatter
{
    protected const MARKERS = ["sourcecode", "php"];

    protected string $hostname = '';

    protected string $appName = '';

    
    public function setHostname(string $hostname): self
    {
        $this->hostname = $hostname;

        return $this;
    }

    
    public function setAppName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    
    public function normalizeRecord(LogRecord $record): array
    {
        $record = parent::normalizeRecord($record);

        if ($this->hostname !== '') {
            $record["hostname"] = $this->hostname;
        }
        if ($this->appName !== '') {
            $record["appname"] = $this->appName;
        }

        $record["@marker"] = static::MARKERS;

        return $record;
    }
}
