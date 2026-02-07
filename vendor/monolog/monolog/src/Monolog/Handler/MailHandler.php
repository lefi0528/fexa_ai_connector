<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\HtmlFormatter;
use Monolog\LogRecord;


abstract class MailHandler extends AbstractProcessingHandler
{
    
    public function handleBatch(array $records): void
    {
        $messages = [];

        foreach ($records as $record) {
            if ($record->level->isLowerThan($this->level)) {
                continue;
            }

            $message = $this->processRecord($record);
            $messages[] = $message;
        }

        if (\count($messages) > 0) {
            $this->send((string) $this->getFormatter()->formatBatch($messages), $messages);
        }
    }

    
    abstract protected function send(string $content, array $records): void;

    
    protected function write(LogRecord $record): void
    {
        $this->send((string) $record->formatted, [$record]);
    }

    
    protected function getHighestRecord(array $records): LogRecord
    {
        $highestRecord = null;
        foreach ($records as $record) {
            if ($highestRecord === null || $record->level->isHigherThan($highestRecord->level)) {
                $highestRecord = $record;
            }
        }

        return $highestRecord;
    }

    protected function isHtmlBody(string $body): bool
    {
        return ($body[0] ?? null) === '<';
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new HtmlFormatter();
    }
}
