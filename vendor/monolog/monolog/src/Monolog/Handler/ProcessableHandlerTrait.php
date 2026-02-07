<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\ResettableInterface;
use Monolog\Processor\ProcessorInterface;
use Monolog\LogRecord;


trait ProcessableHandlerTrait
{
    
    protected array $processors = [];

    
    public function pushProcessor(callable $callback): HandlerInterface
    {
        array_unshift($this->processors, $callback);

        return $this;
    }

    
    public function popProcessor(): callable
    {
        if (\count($this->processors) === 0) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift($this->processors);
    }

    protected function processRecord(LogRecord $record): LogRecord
    {
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }

        return $record;
    }

    protected function resetProcessors(): void
    {
        foreach ($this->processors as $processor) {
            if ($processor instanceof ResettableInterface) {
                $processor->reset();
            }
        }
    }
}
