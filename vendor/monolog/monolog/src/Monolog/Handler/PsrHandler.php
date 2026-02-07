<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Level;
use Psr\Log\LoggerInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;


class PsrHandler extends AbstractHandler implements FormattableHandlerInterface
{
    
    protected LoggerInterface $logger;

    protected FormatterInterface|null $formatter = null;
    private bool $includeExtra;

    
    public function __construct(LoggerInterface $logger, int|string|Level $level = Level::Debug, bool $bubble = true, bool $includeExtra = false)
    {
        parent::__construct($level, $bubble);

        $this->logger = $logger;
        $this->includeExtra = $includeExtra;
    }

    
    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $message = $this->formatter !== null
            ? (string) $this->formatter->format($record)
            : $record->message;

        $context = $this->includeExtra
            ? [...$record->extra, ...$record->context]
            : $record->context;

        $this->logger->log($record->level->toPsrLogLevel(), $message, $context);

        return false === $this->bubble;
    }

    
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        $this->formatter = $formatter;

        return $this;
    }

    
    public function getFormatter(): FormatterInterface
    {
        if ($this->formatter === null) {
            throw new \LogicException('No formatter has been set and this handler does not have a default formatter');
        }

        return $this->formatter;
    }
}
