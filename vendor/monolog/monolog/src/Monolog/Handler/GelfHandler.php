<?php declare(strict_types=1);



namespace Monolog\Handler;

use Gelf\PublisherInterface;
use Monolog\Level;
use Monolog\Formatter\GelfMessageFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;


class GelfHandler extends AbstractProcessingHandler
{
    
    protected PublisherInterface $publisher;

    
    public function __construct(PublisherInterface $publisher, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->publisher = $publisher;
    }

    
    protected function write(LogRecord $record): void
    {
        $this->publisher->publish($record->formatted);
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new GelfMessageFormatter();
    }
}
