<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Monolog\LogRecord;


class TestHandler extends AbstractProcessingHandler
{
    
    protected array $records = [];
    
    protected array $recordsByLevel = [];
    private bool $skipReset = false;

    
    public function getRecords(): array
    {
        return $this->records;
    }

    public function clear(): void
    {
        $this->records = [];
        $this->recordsByLevel = [];
    }

    public function reset(): void
    {
        if (!$this->skipReset) {
            $this->clear();
        }
    }

    public function setSkipReset(bool $skipReset): void
    {
        $this->skipReset = $skipReset;
    }

    
    public function hasRecords(int|string|Level $level): bool
    {
        return isset($this->recordsByLevel[Logger::toMonologLevel($level)->value]);
    }

    
    public function hasRecord(string|array $recordAssertions, Level $level): bool
    {
        if (\is_string($recordAssertions)) {
            $recordAssertions = ['message' => $recordAssertions];
        }

        return $this->hasRecordThatPasses(function (LogRecord $rec) use ($recordAssertions) {
            if ($rec->message !== $recordAssertions['message']) {
                return false;
            }
            if (isset($recordAssertions['context']) && $rec->context !== $recordAssertions['context']) {
                return false;
            }

            return true;
        }, $level);
    }

    public function hasRecordThatContains(string $message, Level $level): bool
    {
        return $this->hasRecordThatPasses(fn (LogRecord $rec) => str_contains($rec->message, $message), $level);
    }

    public function hasRecordThatMatches(string $regex, Level $level): bool
    {
        return $this->hasRecordThatPasses(fn (LogRecord $rec) => preg_match($regex, $rec->message) > 0, $level);
    }

    
    public function hasRecordThatPasses(callable $predicate, Level $level): bool
    {
        $level = Logger::toMonologLevel($level);

        if (!isset($this->recordsByLevel[$level->value])) {
            return false;
        }

        foreach ($this->recordsByLevel[$level->value] as $i => $rec) {
            if ((bool) $predicate($rec, $i)) {
                return true;
            }
        }

        return false;
    }

    
    protected function write(LogRecord $record): void
    {
        $this->recordsByLevel[$record->level->value][] = $record;
        $this->records[] = $record;
    }

    
    public function __call(string $method, array $args): bool
    {
        if ((bool) preg_match('/(.*)(Debug|Info|Notice|Warning|Error|Critical|Alert|Emergency)(.*)/', $method, $matches)) {
            $genericMethod = $matches[1] . ('Records' !== $matches[3] ? 'Record' : '') . $matches[3];
            $level = \constant(Level::class.'::' . $matches[2]);
            $callback = [$this, $genericMethod];
            if (\is_callable($callback)) {
                $args[] = $level;

                return \call_user_func_array($callback, $args);
            }
        }

        throw new \BadMethodCallException('Call to undefined method ' . \get_class($this) . '::' . $method . '()');
    }
}
