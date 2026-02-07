<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Monolog\LogRecord;


class DeduplicationHandler extends BufferHandler
{
    protected string $deduplicationStore;

    protected Level $deduplicationLevel;

    protected int $time;
    protected bool $gc = false;

    
    public function __construct(HandlerInterface $handler, ?string $deduplicationStore = null, int|string|Level $deduplicationLevel = Level::Error, int $time = 60, bool $bubble = true)
    {
        parent::__construct($handler, 0, Level::Debug, $bubble, false);

        $this->deduplicationStore = $deduplicationStore === null ? sys_get_temp_dir() . '/monolog-dedup-' . substr(md5(__FILE__), 0, 20) .'.log' : $deduplicationStore;
        $this->deduplicationLevel = Logger::toMonologLevel($deduplicationLevel);
        $this->time = $time;
    }

    public function flush(): void
    {
        if ($this->bufferSize === 0) {
            return;
        }

        $store = null;

        if (file_exists($this->deduplicationStore)) {
            $store = file($this->deduplicationStore, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        $passthru = null;

        foreach ($this->buffer as $record) {
            if ($record->level->value >= $this->deduplicationLevel->value) {
                $passthru = $passthru === true || !\is_array($store) || !$this->isDuplicate($store, $record);
                if ($passthru) {
                    $line = $this->buildDeduplicationStoreEntry($record);
                    file_put_contents($this->deduplicationStore, $line . "\n", FILE_APPEND);
                    if (!\is_array($store)) {
                        $store = [];
                    }
                    $store[] = $line;
                }
            }
        }

        
        if ($passthru === true || $passthru === null) {
            $this->handler->handleBatch($this->buffer);
        }

        $this->clear();

        if ($this->gc) {
            $this->collectLogs();
        }
    }

    
    protected function isDuplicate(array $store, LogRecord $record): bool
    {
        $timestampValidity = $record->datetime->getTimestamp() - $this->time;
        $expectedMessage = preg_replace('{[\r\n].*}', '', $record->message);
        $yesterday = time() - 86400;

        for ($i = \count($store) - 1; $i >= 0; $i--) {
            list($timestamp, $level, $message) = explode(':', $store[$i], 3);

            if ($level === $record->level->getName() && $message === $expectedMessage && $timestamp > $timestampValidity) {
                return true;
            }

            if ($timestamp < $yesterday) {
                $this->gc = true;
            }
        }

        return false;
    }

    
    protected function buildDeduplicationStoreEntry(LogRecord $record): string
    {
        return $record->datetime->getTimestamp() . ':' . $record->level->getName() . ':' . preg_replace('{[\r\n].*}', '', $record->message);
    }

    private function collectLogs(): void
    {
        if (!file_exists($this->deduplicationStore)) {
            return;
        }

        $handle = fopen($this->deduplicationStore, 'rw+');

        if (false === $handle) {
            throw new \RuntimeException('Failed to open file for reading and writing: ' . $this->deduplicationStore);
        }

        flock($handle, LOCK_EX);
        $validLogs = [];

        $timestampValidity = time() - $this->time;

        while (!feof($handle)) {
            $log = fgets($handle);
            if (\is_string($log) && '' !== $log && substr($log, 0, 10) >= $timestampValidity) {
                $validLogs[] = $log;
            }
        }

        ftruncate($handle, 0);
        rewind($handle);
        foreach ($validLogs as $log) {
            fwrite($handle, $log);
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        $this->gc = false;
    }
}
