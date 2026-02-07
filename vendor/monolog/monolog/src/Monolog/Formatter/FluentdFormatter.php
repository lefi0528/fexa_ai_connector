<?php declare(strict_types=1);



namespace Monolog\Formatter;

use Monolog\Utils;
use Monolog\LogRecord;


class FluentdFormatter implements FormatterInterface
{
    
    protected bool $levelTag = false;

    public function __construct(bool $levelTag = false)
    {
        $this->levelTag = $levelTag;
    }

    public function isUsingLevelsInTag(): bool
    {
        return $this->levelTag;
    }

    public function format(LogRecord $record): string
    {
        $tag = $record->channel;
        if ($this->levelTag) {
            $tag .= '.' . $record->level->toPsrLogLevel();
        }

        $message = [
            'message' => $record->message,
            'context' => $record->context,
            'extra' => $record->extra,
        ];

        if (!$this->levelTag) {
            $message['level'] = $record->level->value;
            $message['level_name'] = $record->level->getName();
        }

        return Utils::jsonEncode([$tag, $record->datetime->getTimestamp(), $message]);
    }

    public function formatBatch(array $records): string
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }
}
