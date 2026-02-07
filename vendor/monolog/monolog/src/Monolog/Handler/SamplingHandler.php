<?php declare(strict_types=1);



namespace Monolog\Handler;

use Closure;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;


class SamplingHandler extends AbstractHandler implements ProcessableHandlerInterface, FormattableHandlerInterface
{
    use ProcessableHandlerTrait;

    
    protected Closure|HandlerInterface $handler;

    protected int $factor;

    
    public function __construct(Closure|HandlerInterface $handler, int $factor)
    {
        parent::__construct();
        $this->handler = $handler;
        $this->factor = $factor;
    }

    public function isHandling(LogRecord $record): bool
    {
        return $this->getHandler($record)->isHandling($record);
    }

    public function handle(LogRecord $record): bool
    {
        if ($this->isHandling($record) && mt_rand(1, $this->factor) === 1) {
            if (\count($this->processors) > 0) {
                $record = $this->processRecord($record);
            }

            $this->getHandler($record)->handle($record);
        }

        return false === $this->bubble;
    }

    
    public function getHandler(LogRecord|null $record = null): HandlerInterface
    {
        if (!$this->handler instanceof HandlerInterface) {
            $handler = ($this->handler)($record, $this);
            if (!$handler instanceof HandlerInterface) {
                throw new \RuntimeException("The factory Closure should return a HandlerInterface");
            }
            $this->handler = $handler;
        }

        return $this->handler;
    }

    
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        $handler = $this->getHandler();
        if ($handler instanceof FormattableHandlerInterface) {
            $handler->setFormatter($formatter);

            return $this;
        }

        throw new \UnexpectedValueException('The nested handler of type '.\get_class($handler).' does not support formatters.');
    }

    
    public function getFormatter(): FormatterInterface
    {
        $handler = $this->getHandler();
        if ($handler instanceof FormattableHandlerInterface) {
            return $handler->getFormatter();
        }

        throw new \UnexpectedValueException('The nested handler of type '.\get_class($handler).' does not support formatters.');
    }
}
