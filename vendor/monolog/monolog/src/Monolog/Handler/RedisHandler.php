<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Predis\Client as Predis;
use Redis;


class RedisHandler extends AbstractProcessingHandler
{
    
    private Predis|Redis $redisClient;
    private string $redisKey;
    protected int $capSize;

    
    public function __construct(Predis|Redis $redis, string $key, int|string|Level $level = Level::Debug, bool $bubble = true, int $capSize = 0)
    {
        $this->redisClient = $redis;
        $this->redisKey = $key;
        $this->capSize = $capSize;

        parent::__construct($level, $bubble);
    }

    
    protected function write(LogRecord $record): void
    {
        if ($this->capSize > 0) {
            $this->writeCapped($record);
        } else {
            $this->redisClient->rpush($this->redisKey, $record->formatted);
        }
    }

    
    protected function writeCapped(LogRecord $record): void
    {
        if ($this->redisClient instanceof Redis) {
            $mode = \defined('Redis::MULTI') ? Redis::MULTI : 1;
            $this->redisClient->multi($mode)
                ->rPush($this->redisKey, $record->formatted)
                ->ltrim($this->redisKey, -$this->capSize, -1)
                ->exec();
        } else {
            $redisKey = $this->redisKey;
            $capSize = $this->capSize;
            $this->redisClient->transaction(function ($tx) use ($record, $redisKey, $capSize) {
                $tx->rpush($redisKey, $record->formatted);
                $tx->ltrim($redisKey, -$capSize, -1);
            });
        }
    }

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter();
    }
}
