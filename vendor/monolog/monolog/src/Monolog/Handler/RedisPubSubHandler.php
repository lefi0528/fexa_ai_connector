<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Predis\Client as Predis;
use Redis;


class RedisPubSubHandler extends AbstractProcessingHandler
{
    
    private Predis|Redis $redisClient;
    private string $channelKey;

    
    public function __construct(Predis|Redis $redis, string $key, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->redisClient = $redis;
        $this->channelKey = $key;

        parent::__construct($level, $bubble);
    }

    
    protected function write(LogRecord $record): void
    {
        $this->redisClient->publish($this->channelKey, $record->formatted);
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter();
    }
}
