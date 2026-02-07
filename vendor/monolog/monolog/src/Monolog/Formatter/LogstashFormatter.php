<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\LogRecord;


class LogstashFormatter extends NormalizerFormatter
{
    
    protected string $systemName;

    
    protected string $applicationName;

    
    protected string $extraKey;

    
    protected string $contextKey;

    
    public function __construct(string $applicationName, ?string $systemName = null, string $extraKey = 'extra', string $contextKey = 'context')
    {
        
        parent::__construct('Y-m-d\TH:i:s.uP');

        $this->systemName = $systemName === null ? (string) gethostname() : $systemName;
        $this->applicationName = $applicationName;
        $this->extraKey = $extraKey;
        $this->contextKey = $contextKey;
    }

    
    public function format(LogRecord $record): string
    {
        $recordData = parent::format($record);

        $message = [
            '@timestamp' => $recordData['datetime'],
            '@version' => 1,
            'host' => $this->systemName,
        ];
        if (isset($recordData['message'])) {
            $message['message'] = $recordData['message'];
        }
        if (isset($recordData['channel'])) {
            $message['type'] = $recordData['channel'];
            $message['channel'] = $recordData['channel'];
        }
        if (isset($recordData['level_name'])) {
            $message['level'] = $recordData['level_name'];
        }
        if (isset($recordData['level'])) {
            $message['monolog_level'] = $recordData['level'];
        }
        if ('' !== $this->applicationName) {
            $message['type'] = $this->applicationName;
        }
        if (\count($recordData['extra']) > 0) {
            $message[$this->extraKey] = $recordData['extra'];
        }
        if (\count($recordData['context']) > 0) {
            $message[$this->contextKey] = $recordData['context'];
        }

        return $this->toJson($message) . "\n";
    }
}
