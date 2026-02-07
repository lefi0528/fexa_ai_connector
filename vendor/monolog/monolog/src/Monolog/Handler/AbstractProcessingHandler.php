<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\LogRecord;


abstract class AbstractProcessingHandler extends AbstractHandler implements ProcessableHandlerInterface, FormattableHandlerInterface
{
    use ProcessableHandlerTrait;
    use FormattableHandlerTrait;

    
    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        if (\count($this->processors) > 0) {
            $record = $this->processRecord($record);
        }

        $record->formatted = $this->getFormatter()->format($record);

        $this->write($record);

        return false === $this->bubble;
    }

    
    abstract protected function write(LogRecord $record): void;

    public function reset(): void
    {
        parent::reset();

        $this->resetProcessors();
    }
}
